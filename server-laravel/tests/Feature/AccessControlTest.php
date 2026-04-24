<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Qameta\Allure\Allure;
use Qameta\Allure\Model\Severity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    
    $adminRole = Role::where('slug', 'admin')->first();
    $biblioRole = Role::where('slug', 'bibliotecario')->first();

    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);

    Allure::epic('Seguridad y Control de Acceso');
    Allure::feature('RBAC (Control de Acceso basado en Roles)');
});

test('bibliotecario cannot delete books', function () {
    Allure::story('Restricciones del Rol Bibliotecario');
    Allure::description('Verifica que un bibliotecario no tenga permisos de eliminación para proteger la integridad de los datos.');
    Allure::severity(Severity::critical());

    $book = Book::factory()->create();

    $this->actingAs($this->bibliotecario)
        ->deleteJson("/api/books/{$book->id}")
        ->assertStatus(403);
    
    $this->assertDatabaseHas('books', ['id' => $book->id]);
});

test('admin can delete books', function () {
    Allure::story('Privilegios del Rol Administrador');
    Allure::description('Verifica que el administrador tenga control total sobre el inventario, incluyendo la eliminación.');
    Allure::severity(Severity::critical());

    $book = Book::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/books/{$book->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('user can logout', function () {
    Allure::story('Gestión de Sesiones');
    Allure::description('Verifica que el sistema cierre correctamente la sesión del usuario.');
    Allure::severity(Severity::normal());

    $this->actingAs($this->admin)
        ->postJson('/api/auth/logout')
        ->assertStatus(200);
});
