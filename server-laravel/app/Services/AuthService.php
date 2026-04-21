<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

use App\Models\Role;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Autentica un usuario y devuelve un token Sanctum.
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return ['success' => false, 'message' => 'Credenciales inválidas.'];
        }

        // Cargar rol y permisos
        $user->load('role.permissions');
        $role = $user->role;
        $permissionSlugs = ($role instanceof Role) ? $role->permissions->pluck('slug')->toArray() : [];

        // Revocar tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken('biblioteca-token', $permissionSlugs ?: [$user->role?->slug ?? 'guest'])->plainTextToken;

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => ($user->role instanceof Role) ? $user->role->slug : $user->role,
                'permissions' => $permissionSlugs,
            ],
        ];
    }

    /**
     * Invalida todos los tokens del usuario autenticado.
     */
    public function logout($user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Registra un nuevo lector y devuelve un token Sanctum.
     */
    public function register(array $data): array
    {
        $lectorRole = Role::where('slug', 'lector')->first();

        $data['password'] = Hash::make($data['password']);
        $data['role_id'] = $lectorRole?->id;

        $user = $this->userRepository->create($data);
        $user->load('role.permissions');
        $permissionSlugs = $user->role ? $user->role->permissions->pluck('slug')->toArray() : [];

        $token = $user->createToken('biblioteca-token', $permissionSlugs ?: ['lector'])->plainTextToken;

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => ($user->role instanceof Role) ? $user->role->slug : 'lector',
                'permissions' => $permissionSlugs,
            ],
        ];
    }
}
