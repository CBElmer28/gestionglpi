<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles y Permisos (Sistema de Accesos)
        $this->call(RolesAndPermissionsSeeder::class);
        $adminRole = Role::where('slug', 'admin')->first();
        $biblioRole = Role::where('slug', 'bibliotecario')->first();

        // 2. Datos Maestros (Necesarios para el catálogo)
        Genre::firstOrCreate(['name' => 'Ficción'], ['glpi_id' => 101]);
        Genre::firstOrCreate(['name' => 'Ciencia Ficción'], ['glpi_id' => 102]);
        Genre::firstOrCreate(['name' => 'Drama'], ['glpi_id' => 103]);

        Publisher::firstOrCreate(['name' => 'Alfaguara'], ['glpi_id' => 201]);
        Publisher::firstOrCreate(['name' => 'Salamandra'], ['glpi_id' => 202]);
        Publisher::firstOrCreate(['name' => 'Debolsillo'], ['glpi_id' => 203]);

        // 3. Usuarios
        User::firstOrCreate(
            ['email' => 'admin@biblioteca.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('admin123'),
                'role_id'  => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name'     => 'Bibliotecario',
                'password' => Hash::make('biblio123'),
                'role_id'  => $biblioRole->id,
            ]
        );

        $this->command->info('✅ Base de Datos inicializada con roles y maestros.');
    }
}
