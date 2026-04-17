<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Listar todos los roles con sus permisos.
     */
    public function index(): JsonResponse
    {
        return response()->json(Role::with('permissions')->get());
    }

    /**
     * Listar todos los permisos disponibles en el sistema.
     */
    public function permissions(): JsonResponse
    {
        return response()->json(Permission::orderBy('name')->get());
    }

    /**
     * Sincronizar permisos para un rol específico.
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->permissions()->sync($request->permissions);

        return response()->json([
            'message' => 'Permisos actualizados correctamente.',
            'role'    => $role->load('permissions')
        ]);
    }
}
