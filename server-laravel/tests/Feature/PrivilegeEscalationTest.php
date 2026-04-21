<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->first()->id
    ]);
    $this->bibliotecario = User::factory()->create([
        'role_id' => Role::where('slug', 'bibliotecario')->first()->id
    ]);
    $this->lector = User::factory()->create([
        'role_id' => Role::where('slug', 'lector')->first()->id
    ]);
});

test('a reader cannot access user management endpoints', function () {
    $this->actingAs($this->lector)
        ->getJson('/api/users')
        ->assertStatus(403);
});

test('a librarian cannot access roles and permissions configuration', function () {
    $this->actingAs($this->bibliotecario)
        ->getJson('/api/roles')
        ->assertStatus(403);
});

test('unauthenticated users are blocked from protected routes (books)', function () {
    $this->getJson('/api/books')
        ->assertStatus(401);
});

test('a reader cannot trigger manual GLPI synchronization', function () {
    $this->actingAs($this->lector)
        ->postJson('/api/glpi/sync-all')
        ->assertStatus(403);
});

test('api ensures sensitive data like passwords are not exposed in user details', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/users/{$this->lector->id}")
        ->assertStatus(200)
        ->assertJsonMissing(['password'])
        ->assertJsonMissing(['remember_token']);
});
