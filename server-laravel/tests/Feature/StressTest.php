<?php

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Qameta\Allure\Allure;
use Qameta\Allure\Model\Severity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->first()->id
    ]);

    Allure::epic('Calidad y Rendimiento');
    Allure::feature('Pruebas de Estrés y Resistencia');
});

test('high volume data response time analysis (Stress Test)', function () {
    Allure::story('Volumetría de Datos');
    Allure::description('Evalúa la latencia de respuesta del sistema al manejar miles de registros de libros en el inventario.');
    Allure::severity(Severity::minor());

    $count = 2000;
    
    $genre = Genre::factory()->create();
    $publisher = Publisher::factory()->create();

    echo "\n[STRESS] Iniciando inyección masiva de {$count} libros...\n";
    $startInject = microtime(true);
    
    Book::factory()->count($count)->create([
        'genre_id' => $genre->id,
        'publisher_id' => $publisher->id
    ]);
    
    $endInject = microtime(true);
    echo "[STRESS] Inyección completada en " . round($endInject - $startInject, 2) . "s\n";

    $startSearch = microtime(true);
    $response = $this->actingAs($this->admin)
                     ->getJson('/api/books/search?q=Libro');
    $endSearch = microtime(true);
    
    $searchTime = round($endSearch - $startSearch, 4);
    echo "[STRESS] Tiempo de búsqueda (Global Search): {$searchTime}s\n";

    $response->assertStatus(200);
    
    $startIndex = microtime(true);
    $response = $this->actingAs($this->admin)
                     ->getJson('/api/books?page=1');
    $endIndex = microtime(true);

    $indexTime = round($endIndex - $startIndex, 4);
    echo "[STRESS] Tiempo de carga de Dashboard (Paginado): {$indexTime}s\n";

    $response->assertStatus(200);
    
    expect($searchTime)->toBeLessThan(0.5);
});

test('sequential requests burst handling capacity', function () {
    Allure::story('Ráfaga de Peticiones');
    Allure::description('Verifica la capacidad del servidor para procesar ráfagas secuenciales de peticiones sin degradación de servicio.');
    Allure::severity(Severity::normal());

    $requests = 50;
    echo "\n[STRESS] Iniciando ráfaga de {$requests} peticiones secuenciales...\n";
    
    $startBurst = microtime(true);
    
    for ($i = 0; $i < $requests; $i++) {
        $this->actingAs($this->admin)->getJson('/api/books');
    }
    
    $endBurst = microtime(true);
    $totalBurst = round($endBurst - $startBurst, 2);
    
    echo "[STRESS] Ráfaga de {$requests} peticiones completada en {$totalBurst}s\n";
    echo "[STRESS] Promedio por petición: " . round($totalBurst / $requests, 4) . "s\n";

    expect($totalBurst)->toBeLessThan(5);
});

test('discovery of synchronous GLPI synchronization limit', function () {
    Allure::story('Punto de Quiebre GLPI');
    Allure::description('Busca el límite de libros que el sistema puede sincronizar antes de superar los 20 segundos.');
    Allure::severity(Severity::critical());

    $bookService = app(\App\Services\BookService::class);
    $genre = Genre::first() ?? Genre::factory()->create();
    $publisher = Publisher::first() ?? Publisher::factory()->create();
    
    $steps = [10, 50, 100, 200];
    echo "\n[STRESS] Buscando límite sincrónico de GLPI...\n";

    foreach ($steps as $count) {
        $createdIds = [];
        $start = microtime(true);

        try {
            for ($i = 0; $i < $count; $i++) {
                $book = $bookService->create([
                    'isbn' => '978' . str_pad($count . $i, 10, '0', STR_PAD_LEFT),
                    'title' => 'Libro Stress ' . $i,
                    'author' => 'Stress Bot',
                    'edition' => '2026',
                    'genre_id' => $genre->id,
                    'publisher_id' => $publisher->id,
                    'status' => 'Disponible'
                ]);
                $createdIds[] = $book->id;
            }
            
            $totalTime = round(microtime(true) - $start, 2);
            echo " >> Bloque {$count} libros: {$totalTime}s\n";

            // Limpieza
            foreach ($createdIds as $id) {
                $bookService->delete($id);
            }

            if ($totalTime > 20) {
                echo "[!] Límite alcanzado con {$count} libros ({$totalTime}s)\n";
                break;
            }
        } catch (\Exception $e) {
            echo "[X] Fallo con {$count} libros: " . $e->getMessage() . "\n";
            break;
        }
    }
});
