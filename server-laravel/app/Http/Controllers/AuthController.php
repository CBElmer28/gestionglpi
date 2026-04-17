<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->login($request->email, $request->password);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 401);
        }

        return response()->json([
            'token' => $result['token'],
            'user'  => $result['user'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('role.permissions');
        
        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role?->slug ?? $user->role,
            'permissions' => $user->role ? $user->role->permissions->pluck('slug')->toArray() : [],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'email.unique' => 'Este correo ya está registrado.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->register($request->only(['name', 'email', 'password']));

        return response()->json([
            'token' => $result['token'],
            'user'  => $result['user'],
        ], 201);
    }
}
