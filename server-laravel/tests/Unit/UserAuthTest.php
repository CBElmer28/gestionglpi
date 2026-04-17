<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que la contraseña se encripte correctamente.
     */
    public function test_password_is_hashed()
    {
        $password = 'secret-123';
        
        $user = User::factory()->create([
            'password' => $password
        ]);

        // La contraseña en la BD no debe ser igual a la de texto plano
        $this->assertNotEquals($password, $user->password);
        
        // Pero el Hash debe validarla correctamente
        $this->assertTrue(Hash::check($password, $user->password));
    }

    /**
     * Verifica los roles asignados.
     */
    public function test_user_roles_logic()
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $bibliotecario = User::factory()->make(['role' => 'bibliotecario']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($bibliotecario->isAdmin());
        $this->assertTrue($bibliotecario->hasRole('bibliotecario'));
    }
}
