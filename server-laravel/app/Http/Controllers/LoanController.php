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

        return response()->json(['message' => $result['message']], 201);
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
