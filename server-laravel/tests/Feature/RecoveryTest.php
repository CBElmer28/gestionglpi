<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use App\Services\GlpiService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Book $book;

    protected function setUp(): void
    {
        parent::setUp();
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
    }

    /**
     * Escenario 1: Fallo de conexión de GLPI (Timeout/Down)
     * OBJETIVO: El sistema local debe seguir funcionando.
     */
    public function test_it_recovers_from_glpi_connection_timeout()
    {
        // Forzar error de conexión o timeout en GLPI
        Http::fake([
            '*' => Http::response('Server Down', 503),
        ]);

        // Intentar actualizar un libro (que dispara sync a GLPI)
        $response = $this->actingAs($this->admin)
                         ->putJson("/api/books/{$this->book->id}", [
                             'title' => 'Título Actualizado Localmente',
                         ]);

        // VERIFICACIÓN:
        // 1. La API devuelve 200 OK (porque el cambio local es prioridad)
        $response->assertStatus(200);

        // 2. La base de datos local se actualizó correctamente
        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => 'Título Actualizado Localmente'
        ]);

        // 3. El error se registró en los logs (aunque no podemos verificar logs fácilmente aquí, 
        // validamos que el flujo no se interrumpió por la excepción capturada en GlpiService)
    }

    /**
     * Escenario 2: Autorecuperación de tokens (Token Refresh)
     * OBJETIVO: Si el token expira (401), el sistema debe re-autenticarse solo.
     */
    public function test_it_automatically_refreshes_token_on_401()
    {
        // 1. Simular un token ya existente en caché
        Cache::put('glpi_session_token', 'old-expired-token');

        // 2. Mock de endpoints específicos
        // Usamos patrones más simples para evitar problemas con backslashes
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
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        
        // El token en caché debe ser el nuevo tras la recuperación
        $this->assertEquals('new-fresh-token', Cache::get('glpi_session_token'));
    }

    /**
     * Escenario 3: Rollback ante fallos críticos de integridad
     * OBJETIVO: Si hay un error de lógica, no deben quedar datos a medias.
     */
    public function test_it_rolls_back_database_on_critical_failure()
    {
        // Este test simula un fallo catastrófico dentro de una transacción
        try {
            \Illuminate\Support\Facades\DB::transaction(function() {
                $this->book->update(['status' => 'Ocupado']);
                
                // Forzar excepción manual simulando un fallo inesperado
                throw new \Exception("Fallo Catastrófico");
            });
        } catch (\Exception $e) {
            // Se esperaba el fallo
        }

        // VERIFICACIÓN:
        // El estado del libro NO debe haber cambiado en la BD debido al rollback
        $this->book->refresh();
        $this->assertEquals('Disponible', $this->book->status);
    }
}
