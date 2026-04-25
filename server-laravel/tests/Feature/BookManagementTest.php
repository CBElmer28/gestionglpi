<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Inicializar roles y permisos
    $this->seed(RolesAndPermissionsSeeder::class);
    $adminRole = Role::where('slug', 'admin')->first();
    $biblioRole = Role::where('slug', 'bibliotecario')->first();

    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
    
    // Mock de GLPI para evitar llamadas reales durante el CRUD
    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'fake'], 200),
        '*/Glpi\CustomAsset\LibrosAsset*' => Http::response(['id' => 777], 201),
    ]);
    
    // Crear datos maestros usando fábricas
    $this->genre = Genre::factory()->create(['name' => 'Ficción']);
    $this->publisher = Publisher::factory()->create(['name' => 'Alfaguara']);
});

test('a librarian can create a book and it persists in the database', function () {
    $bookData = [
        'isbn' => '978' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
        'title' => 'Libro de Prueba',
        'author' => 'Autor Test',
        'edition' => '1ra Edición',
        'genre_id' => $this->genre->id,
        'publisher_id' => $this->publisher->id,
        'status' => 'Disponible'
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $bookData)
        ->assertStatus(201)
        ->assertJsonPath('title', 'Libro de Prueba');

    // Verificar persistencia en MySQL
    $this->assertDatabaseHas('books', [
        'isbn' => $bookData['isbn'],
        'title' => 'Libro de Prueba'
    ]);
});

test('an admin can delete a book and maintain integrity', function () {
    $book = Book::create([
        'isbn' => '978' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
        'title' => 'ABorrar',
        'author' => 'A',
        'edition' => 'X',
        'genre_id' => $this->genre->id,
        'publisher_id' => $this->publisher->id,
        'status' => 'Disponible'
    ]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/books/{$book->id}")
        ->assertStatus(200);

    // Verificar que ya no está en la BD
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('book creation requires mandatory fields validation', function () {
    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', []) // Envío vacío
        ->assertStatus(422)
        ->assertJsonValidationErrors(['isbn', 'title', 'author', 'edition', 'genre_id', 'publisher_id']);
});
