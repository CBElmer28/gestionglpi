<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\GlpiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBookToGlpi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $book;
    protected $action;
    protected $glpiIdToDelete;

    /**
     * Create a new job instance.
     *
     * @param Book|null $book
     * @param string $action
     * @param int|null $glpiIdToDelete
     */
    public function __construct(?Book $book, string $action, ?int $glpiIdToDelete = null)
    {
        $this->book = $book;
        $this->action = $action;
        $this->glpiIdToDelete = $glpiIdToDelete;
    }

    /**
     * Execute the job.
     *
     * @param GlpiService $glpiService
     * @param \App\Services\BookService $bookService
     */
    public function handle(GlpiService $glpiService, \App\Services\BookService $bookService): void
    {
        try {
            switch ($this->action) {
                case 'create':
                    if (!$this->book) return;
                    
                    $preparedData = $this->prepareDataForJob($this->book);
                    
                    $glpiResult = $glpiService->createBook($preparedData);
                    
                    // Extraemos solo el ID si viene en un array
                    $glpiId = is_array($glpiResult) ? ($glpiResult['id'] ?? null) : $glpiResult;

                    if ($glpiId) {
                        $this->book->updateQuietly(['glpi_id' => $glpiId]);
                        Log::info("Libro sincronizado con GLPI (Creado): {$this->book->title} (ID: {$glpiId})");
                    }
                    break;

                case 'update':
                    if (!$this->book || !$this->book->glpi_id) return;
                    
                    $preparedData = $this->prepareDataForJob($this->book);
                    $glpiService->updateBook($this->book->glpi_id, $preparedData);
                    Log::info("Libro actualizado en GLPI: {$this->book->title} (ID: {$this->book->glpi_id})");
                    break;

                case 'delete':
                    if (!$this->glpiIdToDelete) return;
                    $glpiService->deleteBook($this->glpiIdToDelete);
                    Log::info("Libro eliminado de GLPI (ID: {$this->glpiIdToDelete})");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error sincronizando con GLPI ({$this->action}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepara la data del libro para enviar a GLPI, resolviendo IDs de maestros.
     */
    private function prepareDataForJob(Book $book): array
    {
        $data = $book->toArray();
        
        // Cargar relaciones si no están
        $book->loadMissing(['genre', 'publisher']);

        if ($book->genre_id) {
            $genre = \App\Models\Genre::find($book->genre_id);
            $data['glpi_genre_id'] = $genre ? $genre->glpi_id : null;
        }

        if ($book->publisher_id) {
            $publisher = \App\Models\Publisher::find($book->publisher_id);
            $data['glpi_publisher_id'] = $publisher ? $publisher->glpi_id : null;
        }

        $statusMap = [
            'Disponible'   => 1,
            'Prestado'     => 2,
            'Mantenimiento' => 3,
        ];
        $data['glpi_status_id'] = $statusMap[$book->status] ?? 1;

        return $data;
    }
}
