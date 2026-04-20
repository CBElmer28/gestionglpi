<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function __construct(protected BookService $bookService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['genre_id', 'status', 'publisher_id', 'q', 'title', 'author', 'isbn', 'per_page']);
        $books   = $this->bookService->getAll($filters);
        return response()->json($books);
    }

    public function show(int $id): JsonResponse
    {
        $book = $this->bookService->getById($id);
        if (!$book) {
            return response()->json(['message' => 'Libro no encontrado.'], 404);
        }
        return response()->json($book);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasRole('lector')) {
            return response()->json(['message' => 'No tiene permisos para realizar esta acción.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'        => ['required', 'string', 'max:255', new \App\Rules\SafeText],
            'author'       => ['required', 'string', 'max:255', new \App\Rules\SafeText],
            'isbn'         => ['required', 'string', 'max:50', 'unique:books,isbn', new \App\Rules\SafeText],
            'edition'      => ['required', 'string', 'max:100', new \App\Rules\SafeText],
            'genre_id'     => 'required|exists:genres,id',
            'publisher_id' => 'required|exists:publishers,id',
            'status'       => 'nullable|in:Disponible,Prestado,Mantenimiento',
            'synopsis'     => ['nullable', 'string', new \App\Rules\SafeText],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $book = $this->bookService->create($request->all());
        return response()->json($book, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($request->user()->hasRole('lector')) {
            return response()->json(['message' => 'No tiene permisos para realizar esta acción.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'        => ['sometimes', 'required', 'string', 'max:255', new \App\Rules\SafeText],
            'author'       => ['sometimes', 'required', 'string', 'max:255', new \App\Rules\SafeText],
            'isbn'         => ['sometimes', 'required', 'string', 'max:50', "unique:books,isbn,{$id}", new \App\Rules\SafeText],
            'edition'      => ['sometimes', 'required', 'string', 'max:100', new \App\Rules\SafeText],
            'genre_id'     => 'sometimes|required|exists:genres,id',
            'publisher_id' => 'sometimes|required|exists:publishers,id',
            'status'       => 'nullable|in:Disponible,Prestado,Mantenimiento',
            'synopsis'     => ['nullable', 'string', new \App\Rules\SafeText],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $book = $this->bookService->update($id, $request->all());
            return response()->json($book);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Libro no encontrado.'], 404);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // Solo los administradores pueden borrar libros
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'No tiene permisos para eliminar libros.'], 403);
        }

        $deleted = $this->bookService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Libro no encontrado.'], 404);
        }
        return response()->json(['message' => 'Libro eliminado correctamente.']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json(['message' => 'El término de búsqueda debe tener al menos 2 caracteres.'], 422);
        }
        $books = $this->bookService->search($query);
        return response()->json($books);
    }
}
