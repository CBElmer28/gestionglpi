<?php

use App\Services\GlpiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Limpiar cache para asegurar fresh state en cada test
    Cache::forget('glpi_session_token');
    $this->glpiService = new GlpiService();
});

test('getSessionToken obtains and caches the token', function () {
    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'mock-session-123'], 200),
    ]);

    $token = $this->glpiService->getSessionToken();

    expect($token)->toBe('mock-session-123');
    expect(Cache::has('glpi_session_token'))->toBeTrue();
});

test('getSessionToken handles connection errors (GLPI offline)', function () {
    Http::fake([
        '*/initSession' => function () {
            throw new Exception("Connection refused");
        },
    ]);

    // No debe lanzar excepción, sino manejarla y devolver null
    $token = $this->glpiService->getSessionToken();

    expect($token)->toBeNull();
});

test('findUserByEmail builds the search query correctly', function () {
    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'mock-session-123'], 200),
        '*/search/User*' => Http::response([
            'totalcount' => 1,
            'data' => [
                [2 => 85] // ID correpondiendo al campo 2
            ]
        ], 200),
    ]);

    $userId = $this->glpiService->findUserByEmail('test@example.com');

    expect($userId)->toBe(85);

    // Verificar que se envió forcedisplay[0]=2 y criteria[0][value]=test@example.com
    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), 'search/User')) {
            return false;
        }
        $urlDecoded = urldecode($request->url());
        return str_contains($urlDecoded, 'criteria[0][value]=test@example.com') &&
               str_contains($urlDecoded, 'forcedisplay[0]=2');
    });
});

test('createTicket uses the correct POST method', function () {
    Http::fake([
        '*/initSession' => Http::response(['session_token' => 'mock-session-123'], 200),
        '*/Ticket' => Http::response(['id' => 99, 'message' => 'Ticket creado'], 201),
    ]);

    $result = $this->glpiService->createTicket('Titulo', 'Contenido');

    expect($result['id'])->toBe(99);
    
    Http::assertSent(function ($request) {
        return $request->method() === 'POST' && str_contains($request->url(), '/Ticket');
    });
});
