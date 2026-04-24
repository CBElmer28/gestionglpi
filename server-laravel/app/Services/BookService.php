<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Repositories\Contracts\BookRepositoryInterface;

class BookService
{
    public function __construct(
        protected BookRepositoryInterface $bookRepository,
        protected GlpiService $glpiService,
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->bookRepository->all($filters);
    }

    public function getById(int $id): ?Book
    {
        return $this->bookRepository->find($id);
    }

    public function create(array $data): Book
    {
        // 1. Crear en la BD local (fuente de verdad)
        $book = $this->bookRepository->create($data);

        // 2. Sincronizar con GLPI
        $preparedData = $this->prepareBookForGlpi($book);
        $glpiResult = $this->glpiService->createBook($preparedData);
        
        if ($glpiResult && isset($glpiResult['id'])) {
            $this->bookRepository->updateGlpiId($book->id, $glpiResult['id']);
            $book->glpi_id = $glpiResult['id'];
        }

        return $book;
    }

    public function update(int $id, array $data): Book
    {
        $book = $this->bookRepository->update($id, $data);

        // Sincronizar actualización en GLPI si tiene glpi_id
        if ($book->glpi_id) {
            $preparedData = $this->prepareBookForGlpi($book);
            $this->glpiService->updateBook($book->glpi_id, $preparedData);
        }

        return $book;
    }

    public function delete(int $id): bool
    {
        $book = $this->bookRepository->find($id);

        if ($book && $book->glpi_id) {
            $this->glpiService->deleteBook($book->glpi_id);
        }

        return $this->bookRepository->delete($id);
    }

    public function search(string $query)
    {
        return $this->bookRepository->search($query);
    }

    /**
     * Sincronización Bidireccional Completa (Push & Pull).
     */
    public function syncFromGlpi(): array
    {
        $createdLocal = 0;
        $createdGlpi  = 0;
        $updatedLocal = 0;

        // --- FASE 0: MAESTROS ---
        $this->syncGenres();
        $this->syncPublishers();

        // --- FASE 1: PUSH (Local -> GLPI) ---
        $localBooksMissingGlpi = Book::whereNull('glpi_id')->get();
        foreach ($localBooksMissingGlpi as $book) {
            $glpiResult = $this->glpiService->createBook($this->prepareBookForGlpi($book));
            if ($glpiResult && isset($glpiResult['id'])) {
                $this->bookRepository->updateGlpiId($book->id, $glpiResult['id']);
                $createdGlpi++;
            }
        }

        // --- FASE 2: PULL (GLPI -> Local) ---
        $glpiBooks = $this->glpiService->listBooks();
        foreach ($glpiBooks as $glpiBook) {
            $glpiId = (int) $glpiBook['id'];
            $isbn   = $glpiBook['isbn'] ?? null;

            // Encontrar maestros locales
            $genreId = !empty($glpiBook['genre']) && $glpiBook['genre'] !== '—'
                ? Genre::where('name', $glpiBook['genre'])->value('id') : null;
            $publisherId = !empty($glpiBook['publisher']) && $glpiBook['publisher'] !== '—'
                ? Publisher::where('name', $glpiBook['publisher'])->value('id') : null;

            $localLibro = Book::where('glpi_id', $glpiId)
                ->orWhere(function($q) use ($isbn) {
                    if ($isbn && $isbn !== '—') $q->where('isbn', $isbn);
                })
                ->first();

            $data = [
                'glpi_id'      => $glpiId,
                'title'        => $glpiBook['title']  ?? $glpiBook['name'],
                'author'       => $glpiBook['author'] ?? 'Desconocido',
                'isbn'         => $isbn && $isbn !== '—' ? $isbn : ($localLibro->isbn ?? 'TEMP-' . $glpiId),
                'edition'      => $glpiBook['edition'] !== '—' ? $glpiBook['edition'] : ($localLibro->edition ?? null),
                'genre_id'     => $genreId,
                'publisher_id' => $publisherId,
                'synopsis'     => !empty($glpiBook['synopsis']) ? $glpiBook['synopsis'] : ($localLibro->synopsis ?? ''),
            ];

            if (!$localLibro) {
                $data['status'] = 'Disponible';
                $this->bookRepository->create($data);
                $createdLocal++;
            } else {
                $localLibro->update($data);
                $updatedLocal++;
            }
        }

        return [
            'created_local' => $createdLocal,
            'created_glpi'  => $createdGlpi,
            'updated_local' => $updatedLocal,
        ];
    }

    /**
     * Sincroniza los géneros desde GLPI.
     */
    public function syncGenres(): array
    {
        $glpiGenres = $this->glpiService->listGenres();
        $synced = 0;

        foreach ($glpiGenres as $g) {
            // Intentar encontrar por glpi_id primero
            $genre = Genre::where('glpi_id', $g['id'])->first();
            
            // Si no se encuentra, buscar por nombre para corregir posibles IDs corruptos
            if (!$genre) {
                $genre = Genre::where('name', $g['name'])->first();
            }

            if ($genre) {
                $genre->update(['glpi_id' => $g['id'], 'name' => $g['name']]);
            } else {
                Genre::create(['glpi_id' => $g['id'], 'name' => $g['name']]);
            }
            $synced++;
        }

        return ['count' => $synced];
    }

    /**
     * Sincroniza las editoriales desde GLPI.
     */
    public function syncPublishers(): array
    {
        $glpiPublishers = $this->glpiService->listPublishers();
        $synced = 0;

        foreach ($glpiPublishers as $p) {
            // Intentar encontrar por glpi_id primero
            $publisher = Publisher::where('glpi_id', $p['id'])->first();
            
            // Si no se encuentra, buscar por nombre para corregir posibles IDs corruptos
            if (!$publisher) {
                $publisher = Publisher::where('name', $p['name'])->first();
            }

            if ($publisher) {
                $publisher->update(['glpi_id' => $p['id'], 'name' => $p['name']]);
            } else {
                Publisher::create(['glpi_id' => $p['id'], 'name' => $p['name']]);
            }
            $synced++;
        }

        return ['count' => $synced];
    }

    /**
     * Prepara la data del libro para enviar a GLPI, resolviendo IDs de maestros.
     */
    protected function prepareBookForGlpi(Book $book): array
    {
        $data = $book->toArray();
        
        // Cargar relaciones si no están
        if (!$book->relationLoaded('genre') && $book->genre_id) $book->load('genre');
        if (!$book->relationLoaded('publisher') && $book->publisher_id) $book->load('publisher');

        if ($book->genre_id) {
            $data['glpi_genre_id'] = Genre::find($book->genre_id)?->glpi_id;
        }
        
        if ($book->publisher_id) {
            $data['glpi_publisher_id'] = Publisher::find($book->publisher_id)?->glpi_id;
        }

        // Mapeo de Estados Locales -> GLPI (States)
        $statusMap = [
            'Disponible'   => 1, // Nuevo / Disponible
            'Prestado'     => 2, // En uso
            'Mantenimiento' => 3, // En mantenimiento
        ];
        $data['glpi_status_id'] = $statusMap[$book->status] ?? 1;

        return $data;
    }
}
