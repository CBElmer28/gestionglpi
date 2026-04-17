<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GlpiController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistema Biblioteca
|--------------------------------------------------------------------------
*/

// ── Autenticación (pública) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',     [AuthController::class, 'me']);
    });
});

// ── Rutas protegidas (requieren token Sanctum) ───────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Libros ────────────────────────────────────────────────────────────
    Route::get('/books/search', [BookController::class, 'search']);
    Route::apiResource('books', BookController::class);

    // ── Categorías ────────────────────────────────────────────────────────

    // ── Préstamos ─────────────────────────────────────────────────────────
    Route::put('/loans/{id}/return', [LoanController::class, 'returnLoan']);
    Route::apiResource('loans', LoanController::class)->except(['update']);

    // ── Usuarios ──────────────────────────────────────────────────────────
    Route::apiResource('users', UserController::class);

    // ── GLPI ──────────────────────────────────────────────────────────────
    Route::prefix('glpi')->group(function () {
        Route::get('/ping',            [GlpiController::class, 'ping']);
        Route::get('/books',           [GlpiController::class, 'listBooks']);
        Route::get('/genres',          [GlpiController::class, 'listGenres']);
        Route::get('/publishers',      [GlpiController::class, 'listPublishers']);
        Route::get('/tickets',         [GlpiController::class, 'listTickets']);
        Route::post('/sync-book/{id}', [GlpiController::class, 'syncBook']);
        Route::post('/sync-all',       [GlpiController::class, 'syncAll']);
        Route::post('/create-report',  [GlpiController::class, 'createReport']);
    });
});

