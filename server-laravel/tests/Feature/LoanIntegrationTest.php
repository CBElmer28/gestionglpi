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
use Qameta\Allure\Allure;
use Qameta\Allure\Model\Severity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $role = Role::where('slug', 'bibliotecario')->first();
    $this->bibliotecario = User::factory()->create(['role_id' => $role->id]);

    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'fake-token'], 200),
        '*/LibrosAsset/*' => Http::response(['id' => 123, 'message' => 'Sincronizado'], 201),
        '*/LibrosAsset' => Http::response(['id' => 123], 201),
        'PUT */LibrosAsset/*' => Http::response(['message' => 'Updated'], 200),
    ]);

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

    Allure::epic('Operaciones de Préstamos');
    Allure::feature('Gestión Integrada de Préstamos');
});

test('loan creation coordinates book status update to "Prestado"', function () {
    Allure::story('Registro de Préstamo Exitoso');
    Allure::description('Verifica que al registrar un préstamo, el estado del libro cambie automáticamente de Disponible a Prestado.');
    Allure::severity(Severity::critical());

    $payload = [
        'book_id' => $this->book->id,
        'user_name' => 'Juan Pérez',
        'loan_date' => Carbon::today()->toDateString(),
        'return_date' => Carbon::tomorrow()->toDateString(),
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/loans', $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('loans', [
        'book_id' => $this->book->id,
        'user_name' => 'Juan Pérez',
        'status' => 'Activo'
    ]);

    expect($this->book->refresh()->status)->toBe('Prestado');
});

test('returning a loan with an active incident sets book status to "Mantenimiento"', function () {
    Allure::story('Gestión de Devoluciones con Incidencias');
    Allure::description('Verifica que si un libro tiene una incidencia reportada (daños), su estado cambie a Mantenimiento al ser devuelto.');
    Allure::severity(Severity::critical());

    $loan = Loan::create([
        'book_id' => $this->book->id,
        'user_name' => 'Ana Gómez',
        'loan_date' => Carbon::yesterday()->toDateString(),
        'status' => 'Activo'
    ]);
    $this->book->update(['status' => 'Prestado']);

    Report::create([
        'book_id' => $this->book->id,
        'technician_login' => 'soporte_biblioteca',
        'priority' => 'Alta',
        'description' => 'Páginas rotas detectadas por el lector',
    ]);

    $this->actingAs($this->bibliotecario)
        ->putJson("/api/loans/{$loan->id}/return")
        ->assertStatus(200);

    expect($loan->refresh()->status)->toBe('Devuelto');
    expect($this->book->refresh()->status)->toBe('Mantenimiento');
});

test('enforces business rules by preventing loans of unavailable books', function () {
    Allure::story('Validación de Disponibilidad');
    Allure::description('Asegura que el sistema no permita prestar libros que no estén en estado Disponible.');
    Allure::severity(Severity::normal());

    $this->book->update(['status' => 'Mantenimiento']);

    $payload = [
        'book_id' => $this->book->id,
        'user_name' => 'Usuario Impaciente',
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/loans', $payload)
        ->assertStatus(409)
        ->assertJsonFragment(['message' => 'El libro no está disponible para préstamo.']);
});
