<?php

namespace App\Repositories\Contracts;

interface CategoryRepositoryInterface
{
    public function all(): \Illuminate\Database\Eloquent\Collection;
    public function find(int $id): ?\App\Models\Category;
    public function create(array $data): \App\Models\Category;
    public function update(int $id, array $data): \App\Models\Category;
    public function delete(int $id): bool;
}
