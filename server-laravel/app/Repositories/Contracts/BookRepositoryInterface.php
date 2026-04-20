<?php

namespace App\Repositories\Contracts;

interface BookRepositoryInterface
{
    public function all(array $filters = []): \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function find(int $id): ?\App\Models\Book;
    public function create(array $data): \App\Models\Book;
    public function update(int $id, array $data): \App\Models\Book;
    public function delete(int $id): bool;
    public function search(string $query): \Illuminate\Database\Eloquent\Collection;
    public function updateGlpiId(int $id, int $glpiId): void;
}
