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

        return response()->json([
            'stats' => [
                'today'          => Scan::where('created_at', '>=', $today)->count(),
                'new_today'      => Scan::where('created_at', '>=', $today)->where('result', 'nuevo')->count(),
                'existing_today' => Scan::where('created_at', '>=', $today)->where('result', 'existente')->count(),
                'available'      => Book::where('status', 'Disponible')->count(),
            ],
            'data' => $scans,
        ]);
    }

    /** Registra un escaneo (y crea el libro si no existe). */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'isbn'   => ['required', 'string', 'max:30'],
            'title'  => ['nullable', 'string', 'max:255', new \App\Rules\SafeText],
            'author' => ['nullable', 'string', 'max:255', new \App\Rules\SafeText],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isbn = preg_replace('/[^0-9]/', '', (string) $request->input('isbn'));

        if (!$this->validIsbn13($isbn)) {
            return response()->json(['message' => 'El código leído no es un ISBN válido.'], 422);
        }

        $book   = Book::where('isbn', $isbn)->first();
        $result = 'existente';
        $meta   = [];

        if (!$book) {
            $meta   = $this->lookup($isbn);
            $title  = $request->input('title') ?: ($meta['title'] ?? null);
            $author = $request->input('author') ?: ($meta['author'] ?? null);

            if (!$title) {
                return response()->json([
                    'found'   => false,
                    'isbn'    => $isbn,
                    'message' => 'No encontramos datos de este libro. Complétalos manualmente.',
                ], 404);
            }

            $book = Book::create([
                'title'  => mb_substr(strip_tags($title), 0, 255),
                'author' => mb_substr(strip_tags($author ?: 'Autor desconocido'), 0, 255),
                'isbn'   => $isbn,
                'status' => 'Disponible',
            ]);
            $result = 'nuevo';
        } else {
            // Reutiliza portada y editorial de un escaneo anterior del mismo libro
            $last = Scan::where('book_id', $book->id)->latest('id')->first();
            $meta = [
                'publisher' => $last?->publisher,
                'cover_url' => $last?->cover_url,
            ];
        }

        $scan = Scan::create([
            'isbn'      => $isbn,
            'book_id'   => $book->id,
            'user_id'   => $request->user()->id,
            'title'     => $book->title,
            'author'    => $book->author,
            'publisher' => isset($meta['publisher']) ? mb_substr(strip_tags($meta['publisher']), 0, 255) : null,
            'cover_url' => $meta['cover_url'] ?? null,
            'result'    => $result,
            'source'    => 'celular',
        ]);

        return response()->json([
            'message' => $result === 'nuevo' ? 'Libro registrado.' : 'El libro ya existía.',
            'result'  => $result,
            'scan'    => $this->present($scan->load('book')),
        ], 201);
    }

    private function present(Scan $s): array
    {
        $b = $s->book;

        return [
            'id'          => $s->id,
            'isbn'        => $s->isbn,
            'title'       => $b?->title ?? $s->title,
            'author'      => $b?->author ?? $s->author,
            'publisher'   => $s->publisher,
            'cover_url'   => $s->cover_url,
            'result'      => $s->result,
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