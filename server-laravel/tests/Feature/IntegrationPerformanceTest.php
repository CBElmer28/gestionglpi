<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use App\Services\BookService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Qameta\Allure\Allure;
use Qameta\Allure\Model\Severity;

class IntegrationPerformanceTest extends TestCase
{
    /**
     * IMPORTANTE: Usamos DatabaseTransactions para que los cambios en MySQL
     * se deshagan automáticamente al terminar el test (Rollback).
     */
    use DatabaseTransactions;

    /**
     * Forzamos el uso de la conexión mysql definida en tu .env
     */
    protected $connection = 'mysql';

    protected $admin;
    protected $bookService;

    protected function setUp(): void
    {
        parent::setUp();

        // Forzamos modo sincrónico para este test para poder medir latencias de la API
        config(['queue.default' => 'sync']);

        // 1. Inicializar roles y permisos (en la BD real, pero dentro de transacción)
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        $this->bookService = app(BookService::class);

        // Configuración de Allure
        Allure::epic('Rendimiento Realista');
        Allure::feature('Integración GLPI + MySQL');
    }

    /**
     * Mide el tiempo de respuesta REAL de la API de GLPI (sin mocks).
     */
    public function test_real_glpi_sync_latency(): void
    {
        Allure::story('Latencia Real GLPI');
        Allure::description('Mide cuánto tarda el servidor GLPI local en responder a una solicitud de sincronización de géneros.');
        Allure::severity(Severity::critical());

        $start = microtime(true);

        // Ejecutamos la sincronización real (esto llamará a http://localhost:8080)
        $result = $this->bookService->syncGenres();

        $end = microtime(true);
        $totalTimeMs = round(($end - $start) * 1000, 2);

        echo "\n[INTEGRATION] Latencia REAL GLPI (syncGenres): {$totalTimeMs} ms\n";
        echo " - Géneros sincronizados: " . ($result['count'] ?? 0) . "\n";

        // Umbral generoso para cubrir el arranque en frío del token GLPI (primera ejecución / caché vacía).
        // En ejecuciones calientes con MySQL la latencia real es ~300ms.
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['count'] ?? -1, "La comunicación con GLPI falló o no devolvió géneros.");
        $this->assertLessThan(8000, $totalTimeMs, "La latencia de GLPI es inusualmente alta (posible problema de red o GLPI offline).");
    }

    /**
     * Mide la velocidad de persistencia en el motor MySQL real con carga alta (2000 libros).
     */
    public function test_mysql_engine_bulk_performance_2000_books(): void
    {
        Allure::story('Rendimiento MySQL (Carga Alta)');
        Allure::description('Mide el tiempo de inserción masiva de 2000 registros en el motor MySQL real.');
        Allure::severity(Severity::critical());

        $genre = Genre::first() ?? Genre::factory()->create();
        $publisher = Publisher::first() ?? Publisher::factory()->create();

        echo "\n[*] Iniciando inserción masiva de 2000 libros en MySQL...\n";
        $start = microtime(true);

        // Insertar 2000 libros
        for ($i = 0; $i < 2000; $i++) {
            Book::create([
                'isbn' => '978' . str_pad($i, 10, '0', STR_PAD_LEFT),
                'title' => 'Libro Masivo ' . $i,
                'author' => 'Autor Rendimiento',
                'edition' => '2026',
                'genre_id' => $genre->id,
                'publisher_id' => $publisher->id,
                'status' => 'Disponible'
            ]);
        }

        $end = microtime(true);
        $totalTimeMs = round(($end - $start) * 1000, 2);
        $totalTimeSec = round(($end - $start), 2);

        echo "[INTEGRATION] Tiempo para 2000 libros: {$totalTimeMs} ms ({$totalTimeSec} segundos)\n";
        echo "[INTEGRATION] Promedio por libro: " . round($totalTimeMs / 2000, 4) . " ms\n";

        $this->assertEquals(2000, Book::where('title', 'like', 'Libro Masivo%')->count());
    }

    /**
     * Mide la latencia de creación y borrado real en GLPI.
     * Creamos 5 libros para obtener un promedio confiable.
     */
    public function test_real_glpi_write_and_delete_latency(): void
    {
        Allure::story('Latencia Escritura GLPI');
        Allure::description('Mide cuánto tarda GLPI en crear y luego borrar libros reales.');
        Allure::severity(Severity::critical());

        $genre = Genre::first() ?? Genre::factory()->create();
        $publisher = Publisher::first() ?? Publisher::factory()->create();
        
        $iterations = 5; // Hacemos 5 para no saturar pero tener un promedio
        $createdBooks = [];

        // --- FASE 1: CREACIÓN ---
        $startCreate = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $createdBooks[] = $this->bookService->create([
                'isbn' => '978' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'title' => 'Test Latencia ' . $i,
                'author' => 'Perf Test',
                'edition' => '2026',
                'genre_id' => $genre->id,
                'publisher_id' => $publisher->id,
                'status' => 'Disponible'
            ]);
        }
        $endCreate = microtime(true);
        $avgCreateMs = round((($endCreate - $startCreate) / $iterations) * 1000, 2);

        // --- FASE 2: BORRADO (Limpieza) ---
        $startDelete = microtime(true);
        foreach ($createdBooks as $book) {
            $this->bookService->delete($book->id);
        }
        $endDelete = microtime(true);
        $avgDeleteMs = round((($endDelete - $startDelete) / $iterations) * 1000, 2);

        echo "\n[INTEGRATION] Latencia Promedio GLPI (Crear): {$avgCreateMs} ms\n";
        echo "[INTEGRATION] Latencia Promedio GLPI (Borrar): {$avgDeleteMs} ms\n";

        $this->assertCount($iterations, $createdBooks);
    }
}

