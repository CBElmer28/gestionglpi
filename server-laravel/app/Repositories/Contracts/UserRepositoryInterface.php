<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function all(): \Illuminate\Database\Eloquent\Collection;
    public function find(int $id): ?\App\Models\User;
    public function findByEmail(string $email): ?\App\Models\User;
    public function create(array $data): \App\Models\User;
    public function update(int $id, array $data): \App\Models\User;
    public function delete(int $id): bool;
}
