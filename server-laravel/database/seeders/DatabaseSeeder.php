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

        // 2. Datos Maestros (Catálogo Base)
        Genre::updateOrCreate(['glpi_id' => 101], ['name' => 'Terror / Suspenso']);
        Genre::updateOrCreate(['glpi_id' => 102], ['name' => 'Fantasía / Ciencia Ficción']);
        Genre::updateOrCreate(['glpi_id' => 103], ['name' => 'Drama / Realismo']);

        Publisher::updateOrCreate(['glpi_id' => 201], ['name' => 'Alfaguara']);
        Publisher::updateOrCreate(['glpi_id' => 202], ['name' => 'Salamandra']);
        Publisher::updateOrCreate(['glpi_id' => 203], ['name' => 'Debolsillo']);

        // 3. Usuarios Base
        User::firstOrCreate(
            ['email' => 'admin@biblioteca.com'],
            [
                'name'     => 'Administrador del Sistema',
                'password' => Hash::make('admin123'),
                'role_id'  => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name'     => 'Bibliotecario Principal',
                'password' => Hash::make('biblio123'),
                'role_id'  => $biblioRole->id,
            ]
        );

        $this->command->info('✅ Base de Datos inicializada con roles y maestros.');
    }
}
