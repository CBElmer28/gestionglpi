<?php

namespace App\Http\Controllers;

use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    public function __construct(protected LoanService $loanService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['status', 'user_name']);

        // Si no tiene permiso para ver todos, filtramos por su propio ID
        if (!$user->hasPermission('loans.view_all')) {
            $filters['user_id'] = $user->id;
        }

        return response()->json($this->loanService->getAll($filters));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $loan = $this->loanService->getById($id);

        if (!$loan) {
            return response()->json(['message' => 'Préstamo no encontrado.'], 404);
        }

        // Seguridad (IDOR): Verificar si el usuario tiene permiso para ver este préstamo específico
        if (!$user->hasPermission('loans.view_all') && $loan->user_id !== $user->id) {
            return response()->json([
                'message' => 'No tienes permiso para ver los detalles de este préstamo.'
            ], 403);
        }

        return response()->json($loan);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'book_id'     => 'required|integer|exists:books,id',
            'user_name'   => ['required', 'string', 'max:255', new \App\Rules\SafeText],
            'loan_date'   => 'nullable|date',
            'return_date' => 'nullable|date|after_or_equal:loan_date',
            'device_info' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $result = $this->loanService->create($data);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 409);
        }

        // Trazabilidad IoT: Si el préstamo proviene de un escaneo, registrar el evento en scans
        try {
            $book = \App\Models\Book::find($data['book_id']);
            if ($book) {
                $lastScan = \App\Models\Scan::where('book_id', $book->id)->latest('id')->first();
                $device = $request->input('device_info') ?: substr((string) $request->userAgent(), 0, 255);
                \App\Models\Scan::create([
                    'isbn'        => $book->isbn,
                    'book_id'     => $book->id,
                    'user_id'     => $request->user()->id,
                    'title'       => $book->title,
                    'author'      => $book->author,
                    'publisher'   => $lastScan?->publisher,
                    'cover_url'   => $lastScan?->cover_url,
                    'photo_path'  => $lastScan?->photo_path,
                    'result'      => 'prestamo',
                    'source'      => 'celular',
                    'device_info' => $device,
                ]);
            }
        } catch (\Throwable $e) {
            // No bloquear el préstamo si falla la telemetría secundaria
        }

        return response()->json(['message' => $result['message']], 201);
    }

    /**
     * Devolución mediante lector de ISBN, con evidencia fotográfica
     * de la portada y la contraportada del ejemplar devuelto.
     */
    public function returnByIsbn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'isbn'        => ['required', 'string', 'max:30'],
            'front_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'back_photo'  => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isbn  = preg_replace('/[^0-9]/', '', (string) $request->input('isbn'));
        $found = $this->loanService->findOpenLoanByIsbn($isbn);

        if (!$found['success']) {
            return response()->json(['message' => $found['message']], $found['status']);
        }

        $device    = $request->input('device_info') ?: substr((string) $request->userAgent(), 0, 255);
        $frontPath = $request->file('front_photo')->store('returns', 'public');
        $backPath  = $request->file('back_photo')->store('returns', 'public');

        $result = $this->loanService->returnLoan($found['loan']->id, [
            'return_front_photo' => $frontPath,
            'return_back_photo'  => $backPath,
            'return_device'      => $device,
            'returned_by'        => $request->user()->id,
        ]);

        if (!$result['success']) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete([$frontPath, $backPath]);
            return response()->json(['message' => $result['message']], 409);
        }

        $book = $found['book']->fresh();

        // Trazabilidad IoT: evento de devolución con ambas capturas
        try {
            $lastScan = \App\Models\Scan::where('book_id', $book->id)->whereNotNull('cover_url')->latest('id')->first();
            \App\Models\Scan::create([
                'isbn'            => $book->isbn,
                'book_id'         => $book->id,
                'user_id'         => $request->user()->id,
                'title'           => $book->title,
                'author'          => $book->author,
                'publisher'       => $lastScan?->publisher,
                'cover_url'       => $lastScan?->cover_url,
                'photo_path'      => $frontPath,
                'photo_back_path' => $backPath,
                'result'          => 'devolucion',
                'source'          => 'celular',
                'device_info'     => $device,
            ]);
        } catch (\Throwable $e) {
            // No bloquear la devolución si falla la telemetría secundaria
        }

        return response()->json([
            'message'     => $result['message'],
            'book_status' => $book->status,
            'loan'        => $found['loan']->fresh(),
        ]);
    }

    public function returnLoan(int $id): JsonResponse
    {
        $result = $this->loanService->returnLoan($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 409);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->loanService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Préstamo no encontrado.'], 404);
        }
        return response()->json(['message' => 'Préstamo eliminado correctamente.']);
    }
}
