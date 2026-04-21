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
    protected User $lector1;
    protected User $lector2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $biblioRole = Role::where('slug', 'bibliotecario')->first();
        $lectorRole = Role::where('slug', 'lector')->first();

        $this->bibliotecario = User::factory()->create(['role_id' => $biblioRole->id]);
        $this->lector1 = User::factory()->create(['role_id' => $lectorRole->id]);
        $this->lector2 = User::factory()->create(['role_id' => $lectorRole->id]);
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

    /**
     * Prueba de Seguridad: IDOR (Insecure Direct Object Reference).
     * Un lector no debería poder ver ni interactuar con préstamos de otros lectores.
     */
    public function test_lector_cannot_access_other_users_loan()
    {
        // Asegurar que existe un libro
        $book = \App\Models\Book::factory()->create();

        // 1. Lector 2 tiene un préstamo
        $loan = \App\Models\Loan::create([
            'book_id'   => $book->id,
            'user_id'   => $this->lector2->id,
            'user_name' => $this->lector2->name,
            'loan_date' => now()->toDateString(),
            'status'    => 'Activo'
        ]);

        // 2. Lector 1 intenta acceder al detalle del préstamo del Lector 2
        $response = $this->actingAs($this->lector1)
                         ->getJson("/api/loans/{$loan->id}");

        // VERIFICACIÓN: El sistema DEBERÍA denegar el acceso (403) o decir que no existe (404)
        // Actualmente el controlador no tiene esta validación, por lo que este test FALLARÁ si hay vulnerabilidad.
        $response->assertStatus(403);
    }

    /**
     * Prueba de Seguridad: SQL Injection Bypass (Hex encoding).
     */
    public function test_sql_injection_hex_bypass_attempt()
    {
        // Intentar inyectar usando una secuencia hexadecimal que algunos firewalls/filtros ignoran
        // ' OR 1=1 -- en hex
        $payload = [
            'isbn' => '0x27204f5220313d31202d2d', 
            'title' => 'Normal Title',
            'author' => 'Author',
            'edition' => '1st',
            'genre_id' => 1,
            'publisher_id' => 1
        ];

        $response = $this->actingAs($this->bibliotecario)
                         ->postJson('/api/books', $payload);

        // Si el sistema es robusto, debería rechazar el formato extraño o al menos no ejecutarlo.
        // Aquí validamos que si el campo no es estrictamente numérico/isbn válido, falle.
        $response->assertStatus(422);
    }
}
