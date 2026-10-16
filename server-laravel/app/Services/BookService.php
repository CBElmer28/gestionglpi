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

        // 2. Despachar sincronización asíncrona con GLPI
        \App\Jobs\SyncBookToGlpi::dispatch($book, 'create');

        return $book;
    }

    public function update(int $id, array $data): Book
    {
        $book = $this->bookRepository->update($id, $data);

        // 2. Despachar actualización asíncrona si tiene glpi_id
        if ($book->glpi_id) {
            \App\Jobs\SyncBookToGlpi::dispatch($book, 'update');
        }

        return $book;
    }

    public function delete(int $id): bool
    {
        $book = $this->bookRepository->find($id);

        if ($book && $book->glpi_id) {
            // Despachamos el borrado asíncrono pasando el glpi_id explícitamente
            \App\Jobs\SyncBookToGlpi::dispatch(null, 'delete', $book->glpi_id);
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
     * Sincroniza las editoriales con GLPI (bidireccional):
     * 1. Sube editoriales locales sin glpi_id a GLPI.
     * 2. Descarga/actualiza todas las editoriales desde GLPI.
     */
    public function syncPublishers(): array
    {
        $uploaded = 0;
        $synced   = 0;

        // 1. Obtener lista actual en GLPI para cotejar por nombre
        $glpiPublishers = $this->glpiService->listPublishers();
        $glpiMapByName  = [];
        foreach ($glpiPublishers as $gp) {
            if (!empty($gp['name'])) {
                $glpiMapByName[mb_strtolower(trim($gp['name']))] = (int) $gp['id'];
            }
        }

        // 2. Editoriales locales pendientes de sincronizar con GLPI
        $pendingPublishers = Publisher::whereNull('glpi_id')->get();
        foreach ($pendingPublishers as $localPub) {
            $key = mb_strtolower(trim($localPub->name));
            if (isset($glpiMapByName[$key])) {
                // Ya existía en GLPI con ese nombre
                $localPub->update(['glpi_id' => $glpiMapByName[$key]]);
                $uploaded++;
            } else {
                // Crear en GLPI
                $newGlpiId = $this->glpiService->createPublisher($localPub->name);
                if ($newGlpiId) {
                    $localPub->update(['glpi_id' => $newGlpiId]);
                    $glpiMapByName[$key] = $newGlpiId;
                    $uploaded++;
                }
            }
        }

        // 3. Re-sincronizar catálogos desde GLPI
        $refreshedGlpi = $this->glpiService->listPublishers();
        foreach ($refreshedGlpi as $p) {
            $publisher = Publisher::where('glpi_id', $p['id'])->first();

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

        return [
            'count'    => $synced,
            'uploaded' => $uploaded,
        ];
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
