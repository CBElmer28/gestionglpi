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
        $lectorRole = Role::where('slug', 'lector')->first();

        // 2. Datos Maestros (Catálogo Base)
        Genre::updateOrCreate(['glpi_id' => 1], ['name' => 'Terror']);
        Genre::updateOrCreate(['glpi_id' => 2], ['name' => 'Acción y Aventuras']);
        Genre::updateOrCreate(['glpi_id' => 3], ['name' => 'Fantasía']);
        Genre::updateOrCreate(['glpi_id' => 4], ['name' => 'Ciencia Ficción']);
        Genre::updateOrCreate(['glpi_id' => 5], ['name' => 'Policial']);
        Genre::updateOrCreate(['glpi_id' => 6], ['name' => 'Suspenso(Thriller)']);
        Genre::updateOrCreate(['glpi_id' => 7], ['name' => 'Romance']);
        Genre::updateOrCreate(['glpi_id' => 8], ['name' => 'Drama/Realismo']);
        Genre::updateOrCreate(['glpi_id' => 9], ['name' => 'Biografías y Autoayuda']);
        Genre::updateOrCreate(['glpi_id' => 10], ['name' => 'Comedia']);
        Genre::updateOrCreate(['glpi_id' => 11], ['name' => 'Realismo Mágico']);
        Genre::updateOrCreate(['glpi_id' => 12], ['name' => 'Novela']);

        Publisher::updateOrCreate(['glpi_id' => 1], ['name' => 'Planeta']);
        Publisher::updateOrCreate(['glpi_id' => 2], ['name' => 'Seix Barral']);
        Publisher::updateOrCreate(['glpi_id' => 3], ['name' => 'Espasa']);
        Publisher::updateOrCreate(['glpi_id' => 4], ['name' => 'Minotauro']);
        Publisher::updateOrCreate(['glpi_id' => 5], ['name' => 'Destino']);
        Publisher::updateOrCreate(['glpi_id' => 6], ['name' => 'Alfaguara']);
        Publisher::updateOrCreate(['glpi_id' => 7], ['name' => 'Penguin']);
        Publisher::updateOrCreate(['glpi_id' => 8], ['name' => 'Salamandra']);
        Publisher::updateOrCreate(['glpi_id' => 9], ['name' => 'Debolsillo']);
        Publisher::updateOrCreate(['glpi_id' => 10], ['name' => 'Lumen']);
        Publisher::updateOrCreate(['glpi_id' => 11], ['name' => 'Ediciones B']);
        Publisher::updateOrCreate(['glpi_id' => 12], ['name' => 'Anaya']);
        Publisher::updateOrCreate(['glpi_id' => 13], ['name' => 'Algaida']);
        Publisher::updateOrCreate(['glpi_id' => 14], ['name' => 'Anagrama']);
        Publisher::updateOrCreate(['glpi_id' => 15], ['name' => 'Tusquets']);
        Publisher::updateOrCreate(['glpi_id' => 16], ['name' => 'Akal']);
        Publisher::updateOrCreate(['glpi_id' => 17], ['name' => 'Siruela']);
        Publisher::updateOrCreate(['glpi_id' => 18], ['name' => 'Acantilado']);
        Publisher::updateOrCreate(['glpi_id' => 19], ['name' => 'Blackie Books']);
        Publisher::updateOrCreate(['glpi_id' => 20], ['name' => 'Impedimenta']);
        Publisher::updateOrCreate(['glpi_id' => 21], ['name' => 'Siglo XXI']);

        // 3. Usuarios Base
        User::firstOrCreate(
            ['email' => 'admin@biblioteca.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name' => 'Bibliotecario',
                'password' => Hash::make('biblio123'),
                'role_id' => $biblioRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'louisegimenez@biblioteca.com'],
            [
                'name' => 'Louise Matinent',
                'password' => Hash::make('louise123'),
                'role_id' => $biblioRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'juanito@gmail.com'],
            [
                'name' => 'Juanito Perez',
                'password' => Hash::make('juanito123'),
                'role_id' => $lectorRole->id,
            ]
        );

        $this->command->info('✅ Base de Datos inicializada con roles y maestros.');
    }
}
