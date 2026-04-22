<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Genre;
use App\Models\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $biblioRole = Role::where('slug', 'bibliotecario')->first();
    $lectorRole = Role::where('slug', 'lector')->first();

    $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
    $this->lector1 = User::factory()->create(['role_id' => $lectorRole->id]);
    $this->lector2 = User::factory()->create(['role_id' => $lectorRole->id]);
});

test('system rejects XSS payloads in book titles', function () {
    $payload = [
        'isbn' => '123',
        'title' => '<script>alert("hack")</script>',
        'author' => 'Malicious User',
        'edition' => '1st',
        'genre_id' => 1,
        'publisher_id' => 1
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('system rejects SQL comments in ISBN field', function () {
    $payload = [
        'isbn' => '123-- comment',
        'title' => 'Title',
        'author' => 'Author',
        'edition' => '1st',
        'genre_id' => 1,
        'publisher_id' => 1
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['isbn']);
});

test('system rejects multi-statement SQL injection signatures', function () {
    $payload = [
        'isbn' => '123',
        'title' => 'Valid Title',
        'author' => 'Author; DROP TABLE users',
        'edition' => '1st',
        'genre_id' => 1,
        'publisher_id' => 1
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['author']);
});

test('system allows legitimate special characters like quotes in titles', function () {
    // Crear maestros para que no falle por FK
    $genre = Genre::create(['id' => 1, 'name' => 'Tech', 'glpi_id' => 101]);
    $publisher = Publisher::create(['id' => 1, 'name' => "O'Reilly", 'glpi_id' => 102]);

    $payload = [
        'isbn' => '978-123',
        'title' => "O'Reilly Media",
        'author' => "Shaun Wilkinson",
        'edition' => '1st',
        'genre_id' => $genre->id,
        'publisher_id' => $publisher->id
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $payload)
        ->assertStatus(201);
});

test('IDOR protection: readers cannot access other users loan details', function () {
    $book = Book::factory()->create();

    // 1. Lector 2 tiene un préstamo
    $loan = Loan::create([
        'book_id'   => $book->id,
        'user_id'   => $this->lector2->id,
        'user_name' => $this->lector2->name,
        'loan_date' => now()->toDateString(),
        'status'    => 'Activo'
    ]);

    // 2. Lector 1 intenta acceder al detalle del préstamo del Lector 2
    $this->actingAs($this->lector1)
        ->getJson("/api/loans/{$loan->id}")
        ->assertStatus(403);
});

test('detects SQL injection bypass attempts using Hex encoding', function () {
    $payload = [
        'isbn' => '0x27204f5220313d31202d2d', 
        'title' => 'Normal Title',
        'author' => 'Author',
        'edition' => '1st',
        'genre_id' => 1,
        'publisher_id' => 1
    ];

    $this->actingAs($this->bibliotecario)
        ->postJson('/api/books', $payload)
        ->assertStatus(422);
});
