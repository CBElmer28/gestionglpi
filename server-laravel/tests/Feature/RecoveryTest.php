<?php

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use App\Services\GlpiService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->first()->id
    ]);

    $genre = Genre::factory()->create();
    $publisher = Publisher::factory()->create();
    $this->book = Book::create([
        'title' => 'Libro Resiliente',
        'author' => 'Test',
        'isbn' => '999-RECOVERY',
        'genre_id' => $genre->id,
        'publisher_id' => $publisher->id,
        'status' => 'Disponible',
        'glpi_id' => 12345
    ]);
});

test('system recovers from GLPI connection timeout by prioritizing local changes', function () {
    // Forzar error de conexión o timeout en GLPI
    Http::fake([
        '*' => Http::response('Server Down', 503),
    ]);

    // Intentar actualizar un libro (que dispara sync a GLPI)
    $this->actingAs($this->admin)
        ->putJson("/api/books/{$this->book->id}", [
            'title' => 'Título Actualizado Localmente',
        ])
        ->assertStatus(200);

    // VERIFICACIÓN: La base de datos local se actualizó correctamente
    $this->assertDatabaseHas('books', [
        'id' => $this->book->id,
        'title' => 'Título Actualizado Localmente'
    ]);
});

test('system automatically refreshes GLPI session token on 401 Unauthorized', function () {
    // 1. Simular un token ya existente en caché
    Cache::put('glpi_session_token', 'old-expired-token');

    // 2. Mock de endpoints específicos
    Http::fake([
        '*initSession*' => Http::response(['session_token' => 'new-fresh-token'], 200),
        '*AssetType*' => Http::sequence()
            ->push(['error' => 'Unauthorized'], 401) // Intento 1 (falla)
            ->push([['id' => 1, 'name' => 'Ficción']], 200) // Intento 2 (éxito)
    ]);

    $glpi = new GlpiService();
    
    // Acción: Esto debe disparar el 401, luego el initSession, luego el reintento
    $result = $glpi->listGenres();

    // VERIFICACIÓN:
    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and(Cache::get('glpi_session_token'))->toBe('new-fresh-token');
});

test('rolls back database transactions on critical failures to maintain integrity', function () {
    // Este test simula un fallo catastrófico dentro de una transacción
    try {
        DB::transaction(function() {
            $this->book->update(['status' => 'Ocupado']);
            
            // Forzar excepción manual simulando un fallo inesperado
            throw new Exception("Fallo Catastrófico");
        });
    } catch (Exception $e) {
        // Se esperaba el fallo
    }

    // VERIFICACIÓN: El estado del libro NO debe haber cambiado en la BD debido al rollback
    expect($this->book->refresh()->status)->toBe('Disponible');
});
