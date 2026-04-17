<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario admin por defecto
        User::firstOrCreate(
            ['email' => 'admin@biblioteca.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        // Crear usuario bibliotecario de prueba
        User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name'     => 'Bibliotecario',
                'password' => Hash::make('biblio123'),
                'role'     => 'bibliotecario',
            ]
        );

        $this->command->info('✅ Usuarios por defecto creados correctamente.');
        $this->command->info('   Admin: admin@biblioteca.com / admin123');
        $this->command->info('   Bibl.: bibliotecario@biblioteca.com / biblio123');
    }
}
