<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {}

    public function getAll()
    {
        return $this->userRepository->all();
    }

    public function getById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): array
    {
        if ($this->userRepository->findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'El email ya está registrado.'];
        }

        $user = $this->userRepository->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $data['role_id'] ?? null,
        ]);

        return ['success' => true, 'user' => $user->load('role')];
    }

    public function update(int $id, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->update($id, $data);
        return $user->load('role');
    }

    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }
}
