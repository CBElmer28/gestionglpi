<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StressTest extends TestCase
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
     * Prueba de Resistencia: Alta Volumetría.
     * Evalúa el rendimiento de la búsqueda y paginación con miles de registros.
     */
    public function test_high_volume_data_response_time()
    {
        $count = 2000;
        
        // Crear maestros para evitar fallos de FK
        $genre = Genre::factory()->create();
        $publisher = Publisher::factory()->create();

        echo "\n[STRESS] Iniciando inyección masiva de {$count} libros...\n";
        $startInject = microtime(true);
        
        // Inyectar 2,000 libros (Entorno SQLite Protegido)
        Book::factory()->count($count)->create([
            'genre_id' => $genre->id,
            'publisher_id' => $publisher->id
        ]);
        
        $endInject = microtime(true);
        echo "[STRESS] Inyección completada en " . round($endInject - $startInject, 2) . "s\n";

        // --- PRUEBA 1: Búsqueda Global ---
        $startSearch = microtime(true);
        $response = $this->actingAs($this->admin)
                         ->getJson('/api/books/search?q=Libro');
        $endSearch = microtime(true);
        
        $searchTime = round($endSearch - $startSearch, 4);
        echo "[STRESS] Tiempo de búsqueda (Global Search): {$searchTime}s\n";

        $response->assertStatus(200);
        
        // --- PRUEBA 2: Paginación de Dashboard ---
        $startIndex = microtime(true);
        $response = $this->actingAs($this->admin)
                         ->getJson('/api/books?page=1');
        $endIndex = microtime(true);

        $indexTime = round($endIndex - $startIndex, 4);
        echo "[STRESS] Tiempo de carga de Dashboard (Paginado): {$indexTime}s\n";

        $response->assertStatus(200);
        
        // El tiempo de respuesta no debería exceder los 500ms en búsqueda simple
        $this->assertLessThan(0.5, $searchTime, "La búsqueda es demasiado lenta con {$count} registros.");
    }

    /**
     * Prueba de Resistencia: Ráfaga de Peticiones (Simulada).
     * Evalúa la capacidad de respuesta secuencial rápida.
     */
    public function test_sequential_requests_burst()
    {
        $requests = 50;
        echo "[STRESS] Iniciando ráfaga de {$requests} peticiones secuenciales...\n";
        
        $startBurst = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $this->actingAs($this->admin)->getJson('/api/books');
        }
        
        $endBurst = microtime(true);
        $totalBurst = round($endBurst - $startBurst, 2);
        
        echo "[STRESS] Ráfaga de {$requests} peticiones completada en {$totalBurst}s\n";
        echo "[STRESS] Promedio por petición: " . round($totalBurst / $requests, 4) . "s\n";

        $this->assertLessThan(5, $totalBurst, "El servidor tardó demasiado en responder a la ráfaga de peticiones.");
    }
}
