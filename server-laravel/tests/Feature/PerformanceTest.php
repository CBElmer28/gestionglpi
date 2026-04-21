<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->first()->id
        ]);
    }

    /**
     * Rendimiento: Perfilado de Memoria.
     */
    public function test_memory_usage_profiling()
    {
        // 1. Inyectar 1,000 libros
        $genre = Genre::factory()->create();
        $publisher = Publisher::factory()->create();
        Book::factory()->count(1000)->create([
            'genre_id' => $genre->id,
            'publisher_id' => $publisher->id
        ]);

        $memoryBefore = memory_get_usage();
        
        // 2. Ejecutar la acción más pesada (Listar todos con hidratación)
        $this->actingAs($this->admin)->getJson('/api/books?per_page=100');
        
        $memoryAfter = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        $consumed = round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2);
        $peakMb = round($peakMemory / 1024 / 1024, 2);

        echo "\n[PERFORMANCE] Consumo de Memoria (1k libros):\n";
        echo " - Memoria utilizada por la petición: {$consumed} MB\n";
        echo " - Pico de memoria del script: {$peakMb} MB\n";

        // Un pico de > 128MB (límite común de PHP) indicaría un riesgo.
        $this->assertLessThan(128, $peakMb, "El uso de memoria es demasiado alto.");
    }

    /**
     * Rendimiento: Benchmark de Latencia de red simulada.
     * Mide el tiempo de procesamiento interno de la lógica de GLPI.
     */
    public function test_glpi_service_processing_latency()
    {
        // Forzar limpieza de caché para medir el ciclo completo (incluyendo initSession)
        \Illuminate\Support\Facades\Cache::forget('glpi_session_token');

        Http::fake([
            '*initSession*' => Http::response(['session_token' => 'fixed-token'], 200),
            '*search*' => Http::response(['data' => [['2' => 1, '1' => 'Libro Prueba']]], 200),
        ]);

        $start = microtime(true);
        
        $service = new \App\Services\GlpiService();
        $service->listBooks(); 
        
        $end = microtime(true);
        $totalTimeMs = round(($end - $start) * 1000, 2);

        echo "[PERFORMANCE] Latencia de Procesamiento GLPI (Interna + Mock):\n";
        echo " - Tiempo total (init + search + mapping): {$totalTimeMs} ms\n";

        // Debería ser muy rápido (< 500ms incluso en entornos lentos)
        $this->assertLessThan(500, $totalTimeMs, "El procesamiento interno de GLPI es ineficiente.");
    }
}
