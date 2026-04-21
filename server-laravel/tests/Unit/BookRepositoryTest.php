<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Repositories\BookRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BookRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BookRepository();
    }

    /**
     * Camino 1: Filtrado por Género (Nodos 1 -> 2 -> ... -> 11)
     */
    public function test_it_filters_by_genre()
    {
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        Book::factory()->count(3)->create(['genre_id' => $genre1->id]);
        Book::factory()->count(2)->create(['genre_id' => $genre2->id]);

        $results = $this->repository->all(['genre_id' => $genre1->id]);

        $this->assertEquals(3, $results->total());
        foreach ($results as $book) {
            $this->assertEquals($genre1->id, $book->genre_id);
        }
    }

    /**
     * Camino 2: Filtrado por Estatus (Nodos 1 -> 3 -> ... -> 11)
     */
    public function test_it_filters_by_status()
    {
        Book::factory()->count(3)->create(['status' => 'Disponible']);
        Book::factory()->count(2)->create(['status' => 'Prestado']);

        $results = $this->repository->all(['status' => 'Disponible']);

        $this->assertEquals(3, $results->total());
    }

    /**
     * Camino 3: Búsqueda Global (Nodos 1 -> 8 -> ... -> 11)
     */
    public function test_it_filters_by_global_query()
    {
        Book::factory()->create(['title' => 'Quijote de la Mancha']);
        Book::factory()->create(['author' => 'Miguel de Cervantes']);
        Book::factory()->create(['isbn' => '9876543210']);
        Book::factory()->create(['title' => 'Otro Libro']);

        // Búsqueda por título
        $resTitle = $this->repository->all(['q' => 'Quijote']);
        $this->assertEquals(1, $resTitle->total());

        // Búsqueda por autor
        $resAuthor = $this->repository->all(['q' => 'Cervantes']);
        $this->assertEquals(1, $resAuthor->total());
    }

    /**
     * Camino 4: Retorno de todos los registros (Sin paginación) (Nodos 1 -> 9 -> 10)
     */
    public function test_it_returns_all_records_without_pagination()
    {
        Book::factory()->count(15)->create();

        $results = $this->repository->all(['per_page' => 'all']);

        // Debe ser una Collection de Eloquent, no un Paginator
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        $this->assertCount(15, $results);
    }

    /**
     * Pruebas de integración de filtros combinados
     */
    public function test_it_filters_by_multiple_criteria()
    {
        $genre = Genre::factory()->create();
        Book::factory()->create([
            'title' => 'Libro Especial',
            'genre_id' => $genre->id,
            'status' => 'Disponible'
        ]);
        Book::factory()->create(['title' => 'Libro Especial', 'status' => 'Prestado']);

        $results = $this->repository->all([
            'title' => 'Especial',
            'genre_id' => $genre->id,
            'status' => 'Disponible'
        ]);

        $this->assertEquals(1, $results->total());
    }
}
