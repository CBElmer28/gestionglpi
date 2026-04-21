<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BookRepository implements BookRepositoryInterface
{
    public function all(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = Book::with(['genre', 'publisher', 'latestReport']);

        if (!empty($filters['genre_id'])) {
            $query->where('genre_id', $filters['genre_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['publisher_id'])) {
            $query->where('publisher_id', $filters['publisher_id']);
        }

        if (!empty($filters['title'])) {
            $query->where('title', 'like', "%{$filters['title']}%");
        }

        if (!empty($filters['author'])) {
            $query->where('author', 'like', "%{$filters['author']}%");
        }

        if (!empty($filters['isbn'])) {
            $query->where('isbn', 'like', "%{$filters['isbn']}%");
        }

        // Búsqueda simple por texto (compatibilidad con búsqueda global)
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('isbn', 'like', "%{$q}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;

        if ($perPage === 'all') {
            return $query->orderBy('id', 'desc')->get();
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Book
    {
        return Book::with(['genre', 'publisher', 'latestReport'])->find($id);
    }

    public function create(array $data): Book
    {
        $book = Book::create($data);
        return $book->load(['genre', 'publisher', 'latestReport']);
    }

    public function update(int $id, array $data): Book
    {
        $book = Book::findOrFail($id);
        $book->update($data);
        return $book->fresh(['genre', 'publisher', 'latestReport']);
    }

    public function delete(int $id): bool
    {
        return Book::destroy($id) > 0;
    }

    public function search(string $query): Collection
    {
        return Book::with(['genre', 'publisher'])
            ->where('title', 'like', "%{$query}%")
            ->orWhere('author', 'like', "%{$query}%")
            ->orWhere('isbn', 'like', "%{$query}%")
            ->orderBy('id', 'desc')
            ->get();
    }

    public function updateGlpiId(int $id, int $glpiId): void
    {
        Book::where('id', $id)->update(['glpi_id' => $glpiId]);
    }
}
