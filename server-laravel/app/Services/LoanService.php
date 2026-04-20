<?php

namespace App\Services;

use App\Models\Loan;
use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Contracts\LoanRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(
        protected LoanRepositoryInterface $loanRepository,
        protected BookRepositoryInterface $bookRepository,
        protected BookService $bookService,
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->loanRepository->all($filters);
    }

    public function getById(int $id): ?Loan
    {
        return $this->loanRepository->find($id);
    }

    /**
     * Crea un nuevo préstamo verificando que el libro esté disponible.
     */
    public function create(array $data): array
    {
        $book = $this->bookRepository->find($data['book_id']);

        if (!$book) {
            return ['success' => false, 'message' => 'Libro no encontrado.'];
        }

        if ($book->status !== 'Disponible') {
            return ['success' => false, 'message' => 'El libro no está disponible para préstamo.'];
        }

        // Verificar que no haya un préstamo activo para este libro
        $activeLoan = $this->loanRepository->findActiveByBook($data['book_id']);
        if ($activeLoan) {
            return ['success' => false, 'message' => 'El libro ya tiene un préstamo activo.'];
        }

        DB::transaction(function () use ($data, $book) {
            $this->loanRepository->create([
                'book_id'     => $data['book_id'],
                'user_name'   => $data['user_name'],
                'loan_date'   => $data['loan_date'] ?? Carbon::today()->toDateString(),
                'return_date' => $data['return_date'] ?? null,
                'status'      => 'Activo',
            ]);

            // Actualizar estado del libro a "Prestado" y sincronizar con GLPI
            $this->bookService->update($book->id, ['status' => 'Prestado']);
        });

        return ['success' => true, 'message' => 'Préstamo creado correctamente.'];
    }

    /**
     * Marca un préstamo como devuelto.
     */
    public function returnLoan(int $id): array
    {
        $loan = $this->loanRepository->find($id);

        if (!$loan) {
            return ['success' => false, 'message' => 'Préstamo no encontrado.'];
        }

        if ($loan->status === 'Devuelto') {
            return ['success' => false, 'message' => 'Este préstamo ya fue devuelto.'];
        }

        DB::transaction(function () use ($loan) {
            $this->loanRepository->update($loan->id, [
                'status'      => 'Devuelto',
                'return_date' => Carbon::today()->toDateString(),
            ]);

            // Verificar si hubo incidencias reportadas durante el préstamo
            $hasIncidents = \App\Models\Report::where('book_id', $loan->book_id)
                ->where('created_at', '>=', $loan->loan_date)
                ->exists();

            $newStatus = $hasIncidents ? 'Mantenimiento' : 'Disponible';

            // Liberar el libro (o mandarlo a mantenimiento) y sincronizar con GLPI
            $this->bookService->update($loan->book_id, ['status' => $newStatus]);
        });

        return ['success' => true, 'message' => 'Devolución registrada correctamente.'];
    }

    /**
     * Actualiza préstamos vencidos (llamar desde un comando/scheduler).
     */
    public function markOverdueLoans(): int
    {
        $overdueLoans = $this->loanRepository->getOverdueLoans();

        foreach ($overdueLoans as $loan) {
            $this->loanRepository->update($loan->id, ['status' => 'Atrasado']);
        }

        return $overdueLoans->count();
    }

    public function delete(int $id): bool
    {
        return $this->loanRepository->delete($id);
    }
}
