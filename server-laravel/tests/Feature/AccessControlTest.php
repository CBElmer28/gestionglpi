<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    
    $adminRole = Role::where('slug', 'admin')->first();
    $biblioRole = Role::where('slug', 'bibliotecario')->first();

    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
});

test('bibliotecario cannot delete books', function () {
    $book = Book::factory()->create();

    $this->actingAs($this->bibliotecario)
        ->deleteJson("/api/books/{$book->id}")
        ->assertStatus(403);
    
    $this->assertDatabaseHas('books', ['id' => $book->id]);
});

test('admin can delete books', function () {
    $book = Book::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/books/{$book->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('user can logout', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/auth/logout')
        ->assertStatus(200);
});
