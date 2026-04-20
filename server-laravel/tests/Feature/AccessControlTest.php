<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bibliotecario;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $adminRole = Role::where('slug', 'admin')->first();
        $biblioRole = Role::where('slug', 'bibliotecario')->first();

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
    }

    /**
     * Prueba que el bibliotecario NO puede borrar libros.
     */
    public function test_bibliotecario_cannot_delete_books()
    {
        $book = Book::factory()->create();

        $response = $this->actingAs($this->bibliotecario)
                         ->deleteJson("/api/books/{$book->id}");

        // El acceso debería ser denegado (403 Forbidden)
        $response->assertStatus(403);
        
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * Prueba que el admin SÍ puede borrar libros.
     */
    public function test_admin_can_delete_books()
    {
        $book = Book::factory()->create();

        $response = $this->actingAs($this->admin)
                         ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * Prueba el flujo de logout.
     */
    public function test_user_can_logout()
    {
        $response = $this->actingAs($this->admin)
                         ->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }
}
