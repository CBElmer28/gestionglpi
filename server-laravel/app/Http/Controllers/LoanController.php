<?php

namespace App\Http\Controllers;

use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    public function __construct(protected LoanService $loanService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'user_name']);
        return response()->json($this->loanService->getAll($filters));
    }

    public function show(int $id): JsonResponse
    {
        $loan = $this->loanService->getById($id);
        if (!$loan) {
            return response()->json(['message' => 'Préstamo no encontrado.'], 404);
        }
        return response()->json($loan);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'book_id'     => 'required|integer|exists:books,id',
            'user_name'   => ['required', 'string', 'max:255', new \App\Rules\SafeText],
            'loan_date'   => 'nullable|date',
            'return_date' => 'nullable|date|after_or_equal:loan_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->loanService->create($request->all());

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 409);
        }

        return response()->json(['message' => $result['message']], 201);
    }

    public function returnLoan(int $id): JsonResponse
    {
        $result = $this->loanService->returnLoan($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 409);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->loanService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Préstamo no encontrado.'], 404);
        }
        return response()->json(['message' => 'Préstamo eliminado correctamente.']);
    }
}
