<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $bibliotecario;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $biblioRole = Role::where('slug', 'bibliotecario')->first();
        $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
    }

    /**
     * Prueba que el sistema rechaza inyecciones XSS (etiquetas script).
     */
    public function test_rejects_xss_payloads()
    {
        $payload = [
            'isbn' => '123',
            'title' => '<script>alert("hack")</script>',
            'author' => 'Malicious User',
            'edition' => '1st',
            'genre_id' => 1,
            'publisher_id' => 1
        ];

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /**
     * Prueba que el sistema rechaza comentarios SQL sospechosos.
     */
    public function test_rejects_sql_comment_payloads()
    {
        $payload = [
            'isbn' => '123-- comment',
            'title' => 'Title',
            'author' => 'Author',
            'edition' => '1st',
            'genre_id' => 1,
            'publisher_id' => 1
        ];

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * Prueba que el sistema rechaza secuencias multisentencia (; DROP).
     */
    public function test_rejects_sql_injection_signatures()
    {
        $payload = [
            'isbn' => '123',
            'title' => 'Valid Title',
            'author' => 'Author; DROP TABLE users',
            'edition' => '1st',
            'genre_id' => 1,
            'publisher_id' => 1
        ];

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['author']);
    }

    /**
     * Prueba que se permiten nombres legítimos con comillas simples.
     */
    public function test_allows_legitimate_quotes()
    {
        $payload = [
            'isbn' => '978-123',
            'title' => "O'Reilly Media",
            'author' => "Shaun Wilkinson",
            'edition' => '1st',
            'genre_id' => 1,
            'publisher_id' => 1
        ];

        // Crear maestros para que no falle por FK
        \App\Models\Genre::create(['id' => 1, 'name' => 'Tech', 'glpi_id' => 101]);
        \App\Models\Publisher::create(['id' => 1, 'name' => 'O\'Reilly', 'glpi_id' => 102]);

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $payload);

        $response->assertStatus(201);
    }
}
