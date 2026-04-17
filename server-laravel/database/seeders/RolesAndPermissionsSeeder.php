<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Permisos
        $permissions = [
            // Libros
            ['name' => 'Ver Libros',       'slug' => 'books.view'],
            ['name' => 'Gestionar Libros', 'slug' => 'books.manage'],
            // Géneros y Editoriales
            ['name' => 'Gestionar Catálogo', 'slug' => 'catalog.manage'],
            // Préstamos
            ['name' => 'Ver Todos los Préstamos', 'slug' => 'loans.view_all'],
            ['name' => 'Ver Mis Préstamos',        'slug' => 'loans.view_own'],
            ['name' => 'Gestionar Préstamos',      'slug' => 'loans.manage'],
            // Incidencias
            ['name' => 'Reportar Incidencia', 'slug' => 'incidents.report'],
            // Usuarios
            ['name' => 'Gestionar Usuarios', 'slug' => 'users.manage'],
            // GLPI
            ['name' => 'Gestionar GLPI', 'slug' => 'glpi.manage'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Crear Roles y asignar permisos
        
        // Admin: Todo
        $admin = Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Administrador']);
        $admin->permissions()->sync(Permission::all());

        // Bibliotecario: Casi todo menos usuarios y GLPI config?
        $biblio = Role::updateOrCreate(['slug' => 'bibliotecario'], ['name' => 'Bibliotecario']);
        $biblio->permissions()->sync(
            Permission::whereIn('slug', [
                'books.view', 'books.manage', 'catalog.manage',
                'loans.view_all', 'loans.manage', 'incidents.report'
            ])->get()
        );

        // Lector: Solo sus préstamos y reportar
        $lector = Role::updateOrCreate(['slug' => 'lector'], ['name' => 'Lector']);
        $lector->permissions()->sync(
            Permission::whereIn('slug', [
                'loans.view_own', 'incidents.report'
            ])->get()
        );

        // 3. Migrar usuarios existentes
        User::where('role', 'admin')->update(['role_id' => $admin->id]);
        User::where('role', 'bibliotecario')->update(['role_id' => $biblio->id]);
        User::where('role', 'lector')->update(['role_id' => $lector->id]);
    }
}
