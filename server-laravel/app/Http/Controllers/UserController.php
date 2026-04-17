<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->userService->getAll());
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'nullable|in:admin,bibliotecario,lector',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->userService->create($request->all());

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 409);
        }

        return response()->json($result['user']->makeHidden(['password', 'remember_token']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|unique:users,email,{$id}",
            'password' => 'sometimes|string|min:6|confirmed',
            'role'     => 'sometimes|in:admin,bibliotecario,lector',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $this->userService->update($id, $request->all());
            return response()->json($user->makeHidden(['password', 'remember_token']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
