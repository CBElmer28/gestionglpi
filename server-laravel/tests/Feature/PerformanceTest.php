<?php

use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\GlpiService;
use Qameta\Allure\Allure;
use Qameta\Allure\Model\Severity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->first()->id
    ]);

    Allure::epic('Calidad y Rendimiento');
    Allure::feature('Benchmarking de Eficiencia');
});

test('memory usage profiling under load', function () {
    Allure::story('Perfilado de Memoria');
    Allure::description('Mide el impacto en el consumo de memoria RAM al procesar grandes volúmenes de datos hidratados desde la base de datos.');
    Allure::severity(Severity::normal());

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

    expect($peakMb)->toBeLessThan(128);
});

test('glpi service internal processing latency benchmark', function () {
    Allure::story('Latencia de Servicios Externos');
    Allure::description('Mide el tiempo de respuesta interno del servicio GLPI, incluyendo el parseo de respuestas y gestión de sesiones.');
    Allure::severity(Severity::critical());

    Cache::forget('glpi_session_token');

    Http::fake([
        '*initSession*' => Http::response(['session_token' => 'fixed-token'], 200),
        '*search*' => Http::response(['data' => [['2' => 1, '1' => 'Libro Prueba']]], 200),
    ]);

    $start = microtime(true);
    
    $service = new GlpiService();
    $service->listBooks(); 
    
    $end = microtime(true);
    $totalTimeMs = round(($end - $start) * 1000, 2);

    echo "\n[PERFORMANCE] Latencia de Procesamiento GLPI (Interna + Mock):\n";
    echo " - Tiempo total (init + search + mapping): {$totalTimeMs} ms\n";

    expect($totalTimeMs)->toBeLessThan(500);
});
