<?php

use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
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
});

test('loan creation coordinates book status update to "Prestado"', function () {
    $payload = [
        'book_id' => $this->book->id,
        'user_name' => 'Juan Pérez',
        'loan_date' => Carbon::today()->toDateString(),
        'return_date' => Carbon::tomorrow()->toDateString(),
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/loans', $payload)
        ->assertStatus(201);

    // 1. Integración con tabla Loans
    $this->assertDatabaseHas('loans', [
        'book_id' => $this->book->id,
        'user_name' => 'Juan Pérez',
        'status' => 'Activo'
    ]);

    // 2. Integración con tabla Books (Cambio de estado en cascada)
    expect($this->book->refresh()->status)->toBe('Prestado');
});

test('returning a loan with an active incident sets book status to "Mantenimiento"', function () {
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
    $this->actingAs($this->bibliotecario)
        ->putJson("/api/loans/{$loan->id}/return")
        ->assertStatus(200);

    // Verificaciones
    expect($loan->refresh()->status)->toBe('Devuelto');
    expect($this->book->refresh()->status)->toBe('Mantenimiento');
});

test('enforces business rules by preventing loans of unavailable books', function () {
    // 1. Poner el libro en mantenimiento
    $this->book->update(['status' => 'Mantenimiento']);

    $payload = [
        'book_id' => $this->book->id,
        'user_name' => 'Usuario Impaciente',
    ];

    // Acción: Intentar prestar
    $this->actingAs($this->bibliotecario)
        ->postJson('/api/loans', $payload)
        ->assertStatus(409)
        ->assertJsonFragment(['message' => 'El libro no está disponible para préstamo.']);
});
