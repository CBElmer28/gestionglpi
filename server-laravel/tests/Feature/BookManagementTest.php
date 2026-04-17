<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bibliotecario;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->bibliotecario = User::factory()->create(['role' => 'bibliotecario']);
        
        // Mock de GLPI para evitar llamadas reales durante el CRUD
        Http::fake([
            '*/initSession' => Http::response(['session_token' => 'fake'], 200),
            '*/Glpi\CustomAsset\LibrosAsset*' => Http::response(['id' => 777], 201),
        ]);
        
        // Crear datos maestros usando fábricas
        Genre::factory()->create(['name' => 'Ficción']);
        Publisher::factory()->create(['name' => 'Alfaguara']);
    }

    /**
     * Prueba que un bibliotecario puede crear un libro y se guarda en BD.
     */
    public function test_can_create_book()
    {
        $bookData = [
            'isbn' => '1234567890',
            'title' => 'Libro de Prueba',
            'author' => 'Autor Test',
            'genre_id' => 1,
            'publisher_id' => 1,
            'status' => 'Disponible'
        ];

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $bookData);

        $response->assertStatus(201)
                 ->assertJsonPath('title', 'Libro de Prueba');

        // Verificar persistencia en MySQL (Caja Gris)
        $this->assertDatabaseHas('books', [
            'isbn' => '1234567890',
            'title' => 'Libro de Prueba'
        ]);
    }

    /**
     * Prueba que al eliminar un libro no se rompa la integridad.
     */
    public function test_can_delete_book()
    {
        $book = Book::create([
            'isbn' => '999',
            'title' => 'ABorrar',
            'author' => 'A',
            'genre_id' => 1,
            'publisher_id' => 1,
            'status' => 1
        ]);

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(200);
        
        // Verificar que ya no está en la BD
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * Prueba la validación de campos obligatorios.
     */
    public function test_create_book_validation()
    {
        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', []); // Envío vacío

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['isbn', 'title', 'author']);
    }
}
