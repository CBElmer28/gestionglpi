<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GlpiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GlpiServiceTest extends TestCase
{
    protected GlpiService $glpiService;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar cache para asegurar fresh state en cada test
        Cache::forget('glpi_session_token');
        $this->glpiService = new GlpiService();
    }

    /**
     * Prueba que getSessionToken obtiene y cachea el token.
     */
    public function test_get_session_token_success()
    {
        Http::fake([
            '*/initSession' => Http::response(['session_token' => 'mock-session-123'], 200),
        ]);

        $token = $this->glpiService->getSessionToken();

        $this->assertEquals('mock-session-123', $token);
        $this->assertTrue(Cache::has('glpi_session_token'));
    }

    /**
     * Prueba el manejo de errores cuando GLPI está offline (Docker caído).
     */
    public function test_get_session_token_connection_error()
    {
        Http::fake([
            '*/initSession' => function() {
                throw new \Exception("Connection refused");
            },
        ]);

        // No debe lanzar excepción, sino manejarla y devolver null
        $token = $this->glpiService->getSessionToken();

        $this->assertNull($token);
    }

    /**
     * Prueba que findUserByEmail construye la búsqueda correctamente.
     */
    public function test_find_user_by_email_builds_correct_query()
    {
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

        $this->assertEquals(85, $userId);

        // Verificar que se envió forcedisplay[0]=2 y criteria[0][value]=test@example.com
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'search/User')) {
                return false;
            }
            $urlDecoded = urldecode($request->url());
            return str_contains($urlDecoded, 'criteria[0][value]=test@example.com') &&
                   str_contains($urlDecoded, 'forcedisplay[0]=2');
        });
    }

    /**
     * Prueba que createTicket usa el método correcto (POST).
     */
    public function test_create_ticket_calls_post_endpoint()
    {
        Http::fake([
            '*/initSession' => Http::response(['session_token' => 'mock-session-123'], 200),
            '*/Ticket' => Http::response(['id' => 99, 'message' => 'Ticket creado'], 201),
        ]);

        $result = $this->glpiService->createTicket('Titulo', 'Contenido');

        $this->assertEquals(99, $result['id']);
        
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && str_contains($request->url(), '/Ticket');
        });
    }
}
