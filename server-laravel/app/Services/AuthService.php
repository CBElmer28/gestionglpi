<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Autentica un usuario y devuelve un token Sanctum.
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return ['success' => false, 'message' => 'Credenciales inválidas.'];
        }

        // Revocar tokens anteriores (sesión única)
        $user->tokens()->delete();

        $token = $user->createToken('biblioteca-token', [$user->role])->plainTextToken;

        return [
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
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
}
