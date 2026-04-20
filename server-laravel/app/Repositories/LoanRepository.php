<?php

namespace App\Repositories;

use App\Models\Loan;
use App\Repositories\Contracts\LoanRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LoanRepository implements LoanRepositoryInterface
{
    public function all(array $filters = []): LengthAwarePaginator
    {
        $query = Loan::with(['book.latestReport']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['user_name'])) {
            $query->where('user_name', 'like', '%' . $filters['user_name'] . '%');
        }

        return $query->orderBy('id', 'desc')->paginate(15);
    }

    public function find(int $id): ?Loan
    {
        return Loan::with(['book.latestReport'])->find($id);
    }

    public function create(array $data): Loan
    {
        return Loan::create($data);
    }

    public function update(int $id, array $data): Loan
    {
        $loan = Loan::findOrFail($id);
        $loan->update($data);
        return $loan->fresh();
    }

    public function delete(int $id): bool
    {
        return Loan::destroy($id) > 0;
    }

    public function findActiveByBook(int $bookId): ?Loan
    {
        return Loan::where('book_id', $bookId)
            ->where('status', 'Activo')
            ->first();
    }

    public function getOverdueLoans(): Collection
    {
        return Loan::with(['book.latestReport'])
            ->where('status', 'Activo')
            ->whereNotNull('return_date')
            ->where('return_date', '<', Carbon::today())
            ->get();
    }
}
