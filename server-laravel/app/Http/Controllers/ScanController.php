<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Scan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScanController extends Controller
{
    /** Lista de escaneos + indicadores para el dashboard. */
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();

        $scans = Scan::with('book')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Scan $s) => $this->present($s));

        $allScans = Scan::latest('id')->limit(100)->get();
        $deviceCounts = [
            'Celular' => 0,
            'PC / Laptop' => 0,
            'Otros' => 0,
        ];
        foreach ($allScans as $sc) {
            $d = strtolower((string) $sc->device_info);
            if (str_contains($d, 'celular') || str_contains($d, 'android') || str_contains($d, 'iphone') || str_contains($d, 'mobile')) {
                $deviceCounts['Celular']++;
            } elseif (str_contains($d, 'windows') || str_contains($d, 'mac') || str_contains($d, 'pc') || str_contains($d, 'desktop')) {
                $deviceCounts['PC / Laptop']++;
            } else {
                $deviceCounts['Otros']++;
            }
        }

        return response()->json([
            'stats' => [
                'today'          => Scan::where('created_at', '>=', $today)->count(),
                'new_today'      => Scan::where('created_at', '>=', $today)->where('result', 'nuevo')->count(),
                'existing_today' => Scan::where('created_at', '>=', $today)->where('result', 'existente')->count(),
                'available'      => Book::where('status', 'Disponible')->count(),
                'device_counts'  => $deviceCounts,
                'last_sync'      => Scan::latest('id')->value('created_at')?->toIso8601String(),
            ],
            'data' => $scans,
        ]);
    }

    /** Consulta información de un libro por ISBN sin registrar nada en BD. */
    public function lookupIsbn(string $isbn): JsonResponse
    {
        $cleanIsbn = preg_replace('/[^0-9]/', '', $isbn);

        if (!$this->validIsbn13($cleanIsbn)) {
            return response()->json(['message' => 'El código no es un ISBN-13 válido.'], 422);
        }

        $book = Book::where('isbn', $cleanIsbn)->first();
        if ($book) {
            $lastScan = Scan::where('book_id', $book->id)->latest('id')->first();
            
            // Si el libro está prestado, obtener los datos del préstamo activo
            $activeLoan = null;
            if ($book->status === 'Prestado') {
                $loan = \App\Models\Loan::where('book_id', $book->id)
                    ->whereIn('status', ['Activo', 'Atrasado'])
                    ->latest('id')
                    ->first();
                if ($loan) {
                    $activeLoan = [
                        'id'          => $loan->id,
                        'status'      => $loan->status,
                        'user_name'   => $loan->user_name,
                        'loan_date'   => $loan->loan_date?->format('Y-m-d'),
                        'return_date' => $loan->return_date?->format('Y-m-d'),
                    ];
                }
            }

            // Portada oficial: si el último escaneo no tenía cover_url, consultar Open Library
            $coverUrl = $lastScan?->cover_url;
            if (!$coverUrl) {
                $meta = $this->lookup($cleanIsbn);
                $coverUrl = $meta['cover_url'] ?? null;
            }

            return response()->json([
                'exists'      => true,
                'isbn'        => $cleanIsbn,
                'book_id'     => $book->id,
                'title'       => $book->title,
                'author'      => $book->author,
                'status'      => $book->status,
                'publisher'   => $lastScan?->publisher,
                'cover_url'   => $coverUrl,
                'photo_url'   => $lastScan?->photo_path ? '/storage/' . $lastScan->photo_path : null,
                'active_loan' => $activeLoan,
            ]);
        }

        // Si no está registrado en la base de datos, buscar en Open Library
        $meta = $this->lookup($cleanIsbn);

        return response()->json([
            'exists'      => false,
            'isbn'        => $cleanIsbn,
            'title'       => $meta['title'] ?? null,
            'author'      => $meta['author'] ?? null,
            'publisher'   => $meta['publisher'] ?? null,
            'cover_url'   => $meta['cover_url'] ?? null,
            'photo_url'   => null,
            'status'      => 'No catalogado',
            'active_loan' => null,
        ]);
    }

    /** Registra un escaneo (y crea el libro si no existe). */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'isbn'        => ['required', 'string', 'max:30'],
            'title'       => ['nullable', 'string', 'max:255', new \App\Rules\SafeText],
            'author'      => ['nullable', 'string', 'max:255', new \App\Rules\SafeText],
            'publisher'   => ['nullable', 'string', 'max:255', new \App\Rules\SafeText],
            'photo'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isbn = preg_replace('/[^0-9]/', '', (string) $request->input('isbn'));

        if (!$this->validIsbn13($isbn)) {
            return response()->json(['message' => 'El código leído no es un ISBN válido.'], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('scans', 'public');
        }

        $book   = Book::where('isbn', $isbn)->first();
        $result = 'existente';
        $meta   = [];

        if (!$book) {
            $meta      = $this->lookup($isbn);
            $title     = $request->input('title') ?: ($meta['title'] ?? null);
            $author    = $request->input('author') ?: ($meta['author'] ?? null);
            $publisher = $request->input('publisher') ?: ($meta['publisher'] ?? null);

            if (!$title) {
                return response()->json([
                    'found'   => false,
                    'isbn'    => $isbn,
                    'message' => 'No encontramos datos de este libro. Complétalos manualmente.',
                ], 404);
            }

            $publisherModel = null;
            if ($publisher) {
                $cleanPublisherName = mb_substr(strip_tags($publisher), 0, 100);
                $publisherModel = \App\Models\Publisher::firstOrCreate(['name' => $cleanPublisherName]);
            }

            $book = Book::create([
                'title'        => mb_substr(strip_tags($title), 0, 255),
                'author'       => mb_substr(strip_tags($author ?: 'Autor desconocido'), 0, 255),
                'isbn'         => $isbn,
                'publisher'    => $publisherModel ? $publisherModel->name : ($publisher ? mb_substr(strip_tags($publisher), 0, 100) : null),
                'publisher_id' => $publisherModel?->id,
                'status'       => 'Disponible',
            ]);
            $result = 'nuevo';
        } else {
            // Reutiliza portada y editorial de un escaneo anterior o del libro
            $last = Scan::where('book_id', $book->id)->latest('id')->first();
            $meta = [
                'publisher' => $book->publisher ?: $last?->publisher,
                'cover_url' => $last?->cover_url,
            ];
        }

        $device = $request->input('device_info') ?: substr((string) $request->userAgent(), 0, 255);
        $finalPublisher = $request->input('publisher') ?: ($meta['publisher'] ?? $book->publisher);

        $scan = Scan::create([
            'isbn'        => $isbn,
            'book_id'     => $book->id,
            'user_id'     => $request->user()->id,
            'title'       => $book->title,
            'author'      => $book->author,
            'publisher'   => $finalPublisher ? mb_substr(strip_tags($finalPublisher), 0, 255) : null,
            'cover_url'   => $meta['cover_url'] ?? null,
            'photo_path'  => $photoPath,
            'result'      => $result,
            'source'      => 'celular',
            'device_info' => $device,
        ]);

        return response()->json([
            'message' => $result === 'nuevo' ? 'Libro registrado en la biblioteca.' : 'El libro ya existía en catálogo.',
            'result'  => $result,
            'scan'    => $this->present($scan->load('book')),
        ], 201);
    }

    private function present(Scan $s): array
    {
        $b = $s->book;

        // Si es un evento de préstamo, buscar el lector asociado
        $reader = null;
        if (in_array($s->result, ['prestamo', 'devolucion'], true)) {
            $loan = \App\Models\Loan::where('book_id', $s->book_id)->latest('id')->first();
            $reader = $loan?->user_name;
        }

        return [
            'id'          => $s->id,
            'isbn'        => $s->isbn,
            'title'       => $b?->title ?? $s->title,
            'author'      => $b?->author ?? $s->author,
            'publisher'   => $s->publisher,
            'cover_url'   => $s->cover_url,
            'photo_url'   => $s->photo_path ? '/storage/' . $s->photo_path : null,
            'photo_back_url' => $s->photo_back_path ? '/storage/' . $s->photo_back_path : null,
            'device_info' => $s->device_info,
            'result'      => $s->result,
            'reader'      => $reader,
            'scanned_at'  => $s->created_at?->toIso8601String(),
            'book_id'     => $s->book_id,
            'book_status' => $b?->status,
        ];
    }

    /** ISBN-13: 13 dígitos, prefijo 978/979 y dígito verificador correcto. */
    private function validIsbn13(string $isbn): bool
    {
        if (!preg_match('/^97[89][0-9]{10}$/', $isbn)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return ((10 - ($sum % 10)) % 10) === (int) $isbn[12];
    }

    /** Busca datos del libro en Open Library y, si falla, en Google Books. */
    
/** Busca datos del libro en Open Library. */
private function lookup(string $isbn): array
{
    // Primera consulta: API de libros por ISBN.
    try {
        $res = Http::timeout(8)
            ->acceptJson()
            ->get('https://openlibrary.org/api/books', [
                'bibkeys' => "ISBN:$isbn",
                'format'  => 'json',
                'jscmd'   => 'data',
            ]);

        $d = $res->json("ISBN:$isbn");

        if ($res->ok() && is_array($d) && !empty($d['title'])) {
            return [
                'title' => $d['title'] .
                    (!empty($d['subtitle']) ? ': ' . $d['subtitle'] : ''),
                'author' => collect($d['authors'] ?? [])
                    ->pluck('name')
                    ->filter()
                    ->implode(', '),
                'publisher' => collect($d['publishers'] ?? [])
                    ->pluck('name')
                    ->filter()
                    ->first(),
                'cover_url' => $d['cover']['medium']
                    ?? $d['cover']['large']
                    ?? null,
            ];
        }
    } catch (\Throwable $e) {
        Log::warning('Open Library Books API: ' . $e->getMessage());
    }

    // Segunda consulta: buscar por ISBN en el catálogo.
    try {
        $res = Http::timeout(8)
            ->acceptJson()
            ->get('https://openlibrary.org/search.json', [
                'isbn'   => $isbn,
                'fields' => 'title,author_name,publisher,cover_i,isbn',
                'limit'  => 1,
            ]);

        $d = $res->json('docs.0');

        if ($res->ok() && is_array($d) && !empty($d['title'])) {
            $coverUrl = !empty($d['cover_i'])
                ? 'https://covers.openlibrary.org/b/id/'
                    . $d['cover_i'] . '-M.jpg'
                : null;

            return [
                'title' => $d['title'],
                'author' => implode(', ', $d['author_name'] ?? []),
                'publisher' => $d['publisher'][0] ?? null,
                'cover_url' => $coverUrl,
            ];
        }
    } catch (\Throwable $e) {
        Log::warning('Open Library Search API: ' . $e->getMessage());
    }

    return [];
}

}