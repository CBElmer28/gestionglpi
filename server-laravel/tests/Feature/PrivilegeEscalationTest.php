<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bibliotecario;
    protected User $lector;

    protected function setUp(): void
    {
        parent::setUp();
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
    }

    /**
     * Seguridad: Un Lector NO puede acceder a la lista de usuarios.
     */
    public function test_lector_cannot_access_user_management()
    {
        $response = $this->actingAs($this->lector)
                         ->getJson('/api/users');

        $response->assertStatus(403);
    }

    /**
     * Seguridad: Un Bibliotecario NO puede gestionar roles y permisos.
     */
    public function test_bibliotecario_cannot_access_roles_config()
    {
        $response = $this->actingAs($this->bibliotecario)
                         ->getJson('/api/roles');

        $response->assertStatus(403);
    }

    /**
     * Seguridad: Un usuario NO autenticado no puede acceder a nada protegido.
     */
    public function test_unauthenticated_user_cannot_access_books()
    {
        $response = $this->getJson('/api/books');

        $response->assertStatus(401);
    }

    /**
     * Seguridad: Un Lector NO puede forzar la sincronización de GLPI.
     */
    public function test_lector_cannot_sync_glpi()
    {
        $response = $this->actingAs($this->lector)
                         ->postJson('/api/glpi/sync-all');

        $response->assertStatus(403);
    }

    /**
     * Seguridad: Verificación de exclusión de datos sensibles en la API.
     */
    public function test_api_does_not_expose_passwords()
    {
        $response = $this->actingAs($this->admin)
                         ->getJson("/api/users/{$this->lector->id}");

        $response->assertStatus(200);
        
        // El JSON no debe contener la clave 'password' ni el 'remember_token'
        $response->assertJsonMissing(['password']);
        $response->assertJsonMissing(['remember_token']);
    }
}
