<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoanIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $bibliotecario;
    protected Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Configurar Roles y Permisos
        $this->seed(RolesAndPermissionsSeeder::class);
        $role = Role::where('slug', 'bibliotecario')->first();
        $this->bibliotecario = User::factory()->create(['role_id' => $role->id]);

        // 2. Mock de GLPI (Integración externa simulada)
        Http::fake([
            '*/initSession' => Http::response(['session_token' => 'fake-token'], 200),
            '*/LibrosAsset/*' => Http::response(['id' => 123, 'message' => 'Sincronizado'], 201),
            '*/LibrosAsset' => Http::response(['id' => 123], 201),
            'PUT */LibrosAsset/*' => Http::response(['message' => 'Updated'], 200),
        ]);

        // 3. Preparar datos maestros y libro
        $genre = Genre::factory()->create();
        $publisher = Publisher::factory()->create();
        $this->book = Book::create([
            'title' => 'Libro de Integración',
            'author' => 'Autor Test',
            'isbn' => '111222333',
            'edition' => '1ra',
            'genre_id' => $genre->id,
            'publisher_id' => $publisher->id,
            'status' => 'Disponible'
        ]);
    }

    /**
     * Escenario 1: Ciclo de vida completo del préstamo (Éxito)
     * Verifica la integración entre LoanService, BookService y DB.
     */
    public function test_it_coordinates_loan_creation_with_book_status_update()
    {
        $payload = [
            'book_id' => $this->book->id,
            'user_name' => 'Juan Pérez',
            'loan_date' => Carbon::today()->toDateString(),
            'return_date' => Carbon::tomorrow()->toDateString(),
        ];

        // Acción: Crear préstamo vía API
        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/loans', $payload);

        // Verificaciones
        $response->assertStatus(201);

        // 1. Integración con tabla Loans
        $this->assertDatabaseHas('loans', [
            'book_id' => $this->book->id,
            'user_name' => 'Juan Pérez',
            'status' => 'Activo'
        ]);

        // 2. Integración con tabla Books (Cambio de estado en cascada)
        $this->book->refresh();
        $this->assertEquals('Prestado', $this->book->status);
    }

    /**
     * Escenario 2: Devolución con incidencias (Flujo de Mantenimiento)
     * Verifica que si hay reportes pendientes, el libro no se libere a Disponible.
     */
    public function test_it_manages_maintenance_status_on_return_with_incident()
    {
        // 1. Preparar un préstamo activo
        $loan = Loan::create([
            'book_id' => $this->book->id,
            'user_name' => 'Ana Gómez',
            'loan_date' => Carbon::yesterday()->toDateString(),
            'status' => 'Activo'
        ]);
        $this->book->update(['status' => 'Prestado']);

        // 2. Simular que se reportó una incidencia durante el préstamo
        Report::create([
            'book_id' => $this->book->id,
            'technician_login' => 'soporte_biblioteca',
            'priority' => 'Alta',
            'description' => 'Páginas rotas detectadas por el lector',
        ]);

        // 3. Acción: Registrar devolución vía API
        $response = $this->actingAs($this->bibliotecario)
                         ->putJson("/api/loans/{$loan->id}/return");

        // Verificaciones
        $response->assertStatus(200);

        // 1. El préstamo debe estar devuelto
        $this->assertEquals('Devuelto', $loan->refresh()->status);

        // 2. INTEGRACIÓN CRÍTICA: El libro debe pasar a Mantenimiento, NO a Disponible
        $this->book->refresh();
        $this->assertEquals('Mantenimiento', $this->book->status);
    }

    /**
     * Escenario 3: Validación de reglas de negocio en préstamos (Conflicto)
     * Verifica que no se pueda prestar un libro que ya está ocupado.
     */
    public function test_it_enforces_business_rules_on_loan_conflicts()
    {
        // 1. Poner el libro en mantenimiento
        $this->book->update(['status' => 'Mantenimiento']);

        $payload = [
            'book_id' => $this->book->id,
            'user_name' => 'Usuario Impaciente',
        ];

        // Acción: Intentar prestar
        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/loans', $payload);

        // Verificaciones
        $response->assertStatus(409); // Conflict
        $response->assertJsonFragment(['message' => 'El libro no está disponible para préstamo.']);
    }
}
