<?php

namespace App\Repositories\Contracts;

interface LoanRepositoryInterface
{
    public function all(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function find(int $id): ?\App\Models\Loan;
    public function create(array $data): \App\Models\Loan;
    public function update(int $id, array $data): \App\Models\Loan;
    public function delete(int $id): bool;
    public function findActiveByBook(int $bookId): ?\App\Models\Loan;
    public function getOverdueLoans(): \Illuminate\Database\Eloquent\Collection;
}
