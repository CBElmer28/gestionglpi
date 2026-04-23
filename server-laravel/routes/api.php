<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GlpiController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistema Biblioteca
|--------------------------------------------------------------------------
*/

// ── Autenticación (pública) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',     [AuthController::class, 'me']);
    });
});

// ── Rutas protegidas (requieren token Sanctum) ───────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Libros ────────────────────────────────────────────────────────────
    Route::get('/books/search', [BookController::class, 'search']);
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    
    Route::middleware('permission:books.manage')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
    });

    // ── Préstamos ─────────────────────────────────────────────────────────
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{id}', [LoanController::class, 'show']);
    
    Route::middleware('permission:loans.manage')->group(function () {
        Route::post('/loans', [LoanController::class, 'store']);
        Route::put('/loans/{id}/return', [LoanController::class, 'returnLoan']);
        Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
    });

    // ── Usuarios y Roles (Privilegiados) ──────────────────────────────────
    Route::middleware('permission:users.manage')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('/roles',                   [RoleController::class, 'index']);
        Route::get('/permissions',             [RoleController::class, 'permissions']);
        Route::post('/roles/{id}/permissions',  [RoleController::class, 'syncPermissions']);
    });

    // ── GLPI (Administrativo) ─────────────────────────────────────────────
    Route::prefix('glpi')->group(function () {
        Route::get('/ping',            [GlpiController::class, 'ping']);
        Route::get('/books',           [GlpiController::class, 'listBooks']);
        Route::get('/genres',          [GlpiController::class, 'listGenres']);
        Route::get('/publishers',      [GlpiController::class, 'listPublishers']);
        Route::get('/tickets',         [GlpiController::class, 'listTickets']);
        
        Route::middleware('permission:glpi.manage')->group(function () {
            Route::post('/sync-book/{id}',     [GlpiController::class, 'syncBook']);
            Route::post('/sync-all',          [GlpiController::class, 'syncAll']);
            Route::post('/sync-genres',       [GlpiController::class, 'syncGenres']);
            Route::post('/sync-publishers',   [GlpiController::class, 'syncPublishers']);
            Route::post('/create-report',     [GlpiController::class, 'createReport']);
        });
    });
});
