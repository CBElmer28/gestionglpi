<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    /**
     * IMPORTANTE: Hemos eliminado RefreshDatabase para evitar borrados accidentales
     * de la base de datos local 'mysql' durante las pruebas de rendimiento.
     */
    
    protected function setUp(): void
    {
        parent::setUp();

        // BLOQUEO DE SEGURIDAD: Evitar que este test corra en la base de datos real 'mysql'
        if (config('database.default') === 'mysql') {
            $this->markTestSkipped('TEST BLOQUEADO: No se permite ejecutar DatabaseSeederTest en la conexión MySQL principal por seguridad de los datos.');
        }
    }

    /**
     * Verifica que el seeder principal se ejecute sin errores y pueble las tablas.
     */
    public function test_database_seeder_poblates_tables_correctly(): void
    {
        // 1. Ejecutar el seeder
        $this->seed();

        // 2. Verificar Géneros (Se agregaron 12 en el seeder)
        $this->assertEquals(12, Genre::count());
        $this->assertDatabaseHas('genres', [
            'name'    => 'Terror',
            'glpi_id' => 1
        ]);
        $this->assertDatabaseHas('genres', [
            'name'    => 'Novela',
            'glpi_id' => 12
        ]);

        // 3. Verificar Editoriales (Se agregaron 21 en el seeder)
        $this->assertEquals(21, Publisher::count());
        $this->assertDatabaseHas('publishers', [
            'name'    => 'Planeta',
            'glpi_id' => 1
        ]);
        $this->assertDatabaseHas('publishers', [
            'name'    => 'Siglo XXI',
            'glpi_id' => 21
        ]);

        // 4. Verificar Usuarios Base (Admin, Bibliotecario, Louise, Juanito)
        $this->assertEquals(4, User::count());
        $this->assertDatabaseHas('users', [
            'email' => 'admin@biblioteca.com',
            'name'  => 'Administrador'
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'juanito@gmail.com',
            'name'  => 'Juanito Perez'
        ]);

        // 5. Verificar Roles (Deberían estar creados por RolesAndPermissionsSeeder)
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'bibliotecario']);
        $this->assertDatabaseHas('roles', ['slug' => 'lector']);
    }
}
