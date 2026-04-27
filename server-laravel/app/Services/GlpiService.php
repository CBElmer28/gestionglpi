<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlpiService
{
    protected string $baseUrl;
    protected string $appToken;
    protected string $userToken;
    protected string $bookItemtype;
    protected int    $sessionTtl;

    public function __construct()
    {
        $this->baseUrl      = config('glpi.url');
        $this->appToken     = config('glpi.app_token');
        $this->userToken    = config('glpi.user_token');
        $this->bookItemtype = config('glpi.book_itemtype'); // Glpi\CustomAsset\LibrosAsset
        $this->sessionTtl   = config('glpi.session_ttl', 3000);
    }

    /**
     * Construye la URL del endpoint para el tipo personalizado.
     */
    protected function bookEndpoint(): string
    {
        return "{$this->baseUrl}/{$this->bookItemtype}";
    }

    /**
     * Obtiene un session_token reutilizable (cacheado).
     */
    public function getSessionToken(): ?string
    {
        return Cache::remember('glpi_session_token', $this->sessionTtl, function () {
            try {
                $response = Http::timeout(5)->withHeaders([
                    'App-Token'     => $this->appToken,
                    'Authorization' => "user_token {$this->userToken}",
                    'Content-Type'  => 'application/json',
                ])->get("{$this->baseUrl}/initSession");

                if ($response->successful()) {
                    return $response->json('session_token');
                }

                Log::error('GLPI initSession failed', ['response' => $response->body()]);
                return null;
            } catch (\Exception $e) {
                Log::error('GLPI connection error', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Invalida el session_token cacheado.
     */
    public function invalidateSession(): void
    {
        Cache::forget('glpi_session_token');
    }

    /**
     * Cabeceras comunes para requests autenticados.
     */
    protected function authHeaders(): array
    {
        return [
            'App-Token'     => $this->appToken,
            'Session-Token' => $this->getSessionToken() ?? '',
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Ejecuta un request HTTP con reintento automático.
     */
    protected function request(string $method, string $url, array $options = [])
    {
        $doRequest = function () use ($method, $url, $options) {
            $http = Http::timeout(5)->withHeaders($this->authHeaders());
            return match ($method) {
                'GET'    => $http->get($url, $options['query'] ?? []),
                'POST'   => $http->post($url, $options['json'] ?? []),
                'PUT'    => $http->put($url, $options['json'] ?? []),
                'DELETE' => $http->delete($url, $options['json'] ?? []),
            };
        };

        $response = $doRequest();

        if ($response->status() === 401) {
            $this->invalidateSession();
            $response = $doRequest();
        }

        return $response;
    }

    /**
     * Mapea un ítem del resultado de búsqueda de GLPI a nuestra estructura de Libro.
     * IDs: 45001:Título, 45002:Autor, 45003:ISBN, 45004:Sinopsis, 45005:Edición, 45006:Género, 31:Estado, 45008:Editorial
     */
    protected function mapSearchResultToBook(array $item): array
    {
        return [
            'id'        => $item[2]     ?? null,
            'title'     => $item[45001] ?? $item[1] ?? 'Sin Título',
            'name'      => $item[45001] ?? $item[1] ?? 'Sin Título',
            'author'    => $item[45002] ?? '—',
            'isbn'      => $item[45003] ?? '—',
            'synopsis'  => isset($item[45004]) ? strip_tags((string) $item[45004]) : '',
            'edition'   => $item[45005] ?? '—',
            'genre'     => $item[45006] ?? '—',
            'status_label' => $item[31] ?? '—',
            'publisher' => $item[45008] ?? '—',
            'date_mod'  => $item[19]    ?? '',
        ];
    }

    /**
     * Obtiene el detalle completo de un libro.
     */
    public function getBookDetail(int $glpiId): ?array
    {
        try {
            $query = [
                'criteria' => [
                    ['field' => 2, 'searchtype' => 'equals', 'value' => $glpiId]
                ],
                'forcedisplay' => [2, 19, 31, 45001, 45002, 45003, 45004, 45005, 45006, 45008]
            ];

            $response = $this->request('GET', "{$this->baseUrl}/search/{$this->bookItemtype}", ['query' => $query]);

            if (!$response->successful() || !isset($response->json()['data'][0])) {
                return null;
            }

            return $this->mapSearchResultToBook($response->json()['data'][0]);
        } catch (\Exception $e) {
            Log::error("GLPI getBookDetail({$glpiId}) error", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Lista todos los libros en GLPI.
     */
    public function listBooks(): array
    {
        try {
            $query = [
                'range'        => '0-500',
                'forcedisplay' => [2, 19, 31, 45001, 45002, 45003, 45004, 45005, 45006, 45008]
            ];

            $response = $this->request('GET', "{$this->baseUrl}/search/{$this->bookItemtype}", [
                'query' => $query,
            ]);

            if (!$response->successful()) {
                Log::warning('GLPI listBooks failed', ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            $books = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    $books[] = $this->mapSearchResultToBook($item);
                }
            }

            return $books;
        } catch (\Exception $e) {
            Log::error('GLPI listBooks error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Lista todos los Géneros.
     */
    public function listGenres(): array
    {
        try {
            $genreItemtype = 'Glpi\CustomAsset\LibrosAssetType';
            $response = $this->request('GET', "{$this->baseUrl}/{$genreItemtype}", [
                'query' => ['range' => '0-200']
            ]);
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error('GLPI listGenres error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Lista todas las Editoriales.
     */
    public function listPublishers(): array
    {
        try {
            $response = $this->request('GET', "{$this->baseUrl}/Manufacturer", [
                'query' => ['range' => '0-200']
            ]);
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error('GLPI listPublishers error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Crea un Libro en GLPI.
     * Mapeo: 1:Título, 2:Autor, 3:ISBN, 4:Sinopsis, 5:Edición, 6:Género, 7:Estado, 8:Editorial
     */
    public function createBook(array $bookData): ?array
    {
        try {
            $payload = [
                'input' => [
                    'name'                => $bookData['title'] ?? 'Nuevo Libro',
                    'states_id'           => $bookData['glpi_status_id'] ?? 1,
                    'entities_id'         => 0, // Entidad Raíz obligatoria para GLPI
                    'is_recursive'        => 1, // Permite visibilidad en sub-entidades
                    'custom_titulo'       => $bookData['title'] ?? '',
                    'custom_autor'        => $bookData['author'] ?? '',
                    'custom_isbn'         => $bookData['isbn'] ?? '',
                    'custom_sinopsis'     => $bookData['synopsis'] ?? '',
                    'custom_edicion__ano' => $bookData['edition'] ?? '',
                    'custom_genero'       => $bookData['glpi_genre_id'] ?? '',
                    'custom_editorial'    => $bookData['glpi_publisher_id'] ?? '',
                    'comment'             => "ISBN: " . ($bookData['isbn'] ?? '') . " | Sincronizado desde Sistema Biblioteca",
                ],
            ];

            $response = $this->request('POST', $this->bookEndpoint(), ['json' => $payload]);

            if ($response->successful()) {
                $glpiResult = $response->json();
                
                // PARCHE: La API del plugin CustomAsset de GLPI ignora los Dropdowns en el POST (creación).
                // Para solucionar esto, enviamos inmediatamente un PUT minimalista SOLO con los Dropdowns.
                if (isset($glpiResult['id']) && (!empty($payload['input']['custom_genero']) || !empty($payload['input']['custom_editorial']))) {
                    $patchPayload = ['input' => ['id' => $glpiResult['id']]];
                    if (!empty($payload['input']['custom_genero'])) {
                        $patchPayload['input']['custom_genero'] = $payload['input']['custom_genero'];
                    }
                    if (!empty($payload['input']['custom_editorial'])) {
                        $patchPayload['input']['custom_editorial'] = $payload['input']['custom_editorial'];
                    }
                    
                    sleep(1);
                    $patchResponse = $this->request('PUT', "{$this->bookEndpoint()}/{$glpiResult['id']}", ['json' => $patchPayload]);
                    if (!$patchResponse->successful()) {
                        Log::warning('GLPI dropdown patch failed on create', ['status' => $patchResponse->status(), 'id' => $glpiResult['id']]);
                    }
                }

                return $glpiResult;
            }

            Log::warning('GLPI createBook failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('GLPI createBook error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Actualiza un Libro en GLPI.
     */
    public function updateBook(int $glpiId, array $bookData): bool
    {
        try {
            $payload = [
                'input' => [
                    'id'                  => $glpiId,
                    'name'                => ($bookData['title'] ?? '') . ' — ' . ($bookData['author'] ?? ''),
                    'states_id'           => $bookData['glpi_status_id'] ?? 1,
                    'entities_id'         => 0, // Entidad Raíz obligatoria para GLPI
                    'is_recursive'        => 1, // Permite visibilidad en sub-entidades
                    'custom_titulo'       => $bookData['title'] ?? '',
                    'custom_autor'        => $bookData['author'] ?? '',
                    'custom_isbn'         => $bookData['isbn'] ?? '',
                    'custom_sinopsis'     => $bookData['synopsis'] ?? '',
                    'custom_edicion__ano' => $bookData['edition'] ?? '',
                    'custom_genero'       => $bookData['glpi_genre_id'] ?? '',
                    'custom_editorial'    => $bookData['glpi_publisher_id'] ?? '',
                    'comment'             => "ISBN: " . ($bookData['isbn'] ?? ''),
                ],
            ];

            $response = $this->request('PUT', "{$this->bookEndpoint()}/{$glpiId}", ['json' => $payload]);
            
            if ($response->successful()) {
                // PARCHE: Al igual que en createBook, la actualización masiva ignora los Dropdowns.
                // Aplicamos el parche minimalista inmediatamente después de actualizar.
                if (!empty($payload['input']['custom_genero']) || !empty($payload['input']['custom_editorial'])) {
                    $patchPayload = ['input' => ['id' => $glpiId]];
                    if (!empty($payload['input']['custom_genero'])) {
                        $patchPayload['input']['custom_genero'] = $payload['input']['custom_genero'];
                    }
                    if (!empty($payload['input']['custom_editorial'])) {
                        $patchPayload['input']['custom_editorial'] = $payload['input']['custom_editorial'];
                    }
                    
                    sleep(1);
                    $patchResponse = $this->request('PUT', "{$this->bookEndpoint()}/{$glpiId}", ['json' => $patchPayload]);
                    if (!$patchResponse->successful()) {
                        Log::warning("GLPI dropdown patch failed on update {$glpiId}", ['status' => $patchResponse->status()]);
                    }
                }
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('GLPI updateBook error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Elimina un Libro en GLPI.
     */
    public function deleteBook(int $glpiId): bool
    {
        try {
            $response = $this->request('DELETE', "{$this->bookEndpoint()}/{$glpiId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('GLPI deleteBook error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Busca un usuario por su login en GLPI.
     */
    public function findUserByLogin(string $login): ?int
    {
        try {
            // Usamos search/User con criteria para mayor fiabilidad (Campo 1: Login)
            $response = $this->request('GET', "{$this->baseUrl}/search/User", [
                'query' => [
                    'criteria[0][field]'      => 1,
                    'criteria[0][searchtype]' => 'contains',
                    'criteria[0][value]'      => $login,
                    'forcedisplay[0]'         => 2, // ID
                    'range'                   => '0-1'
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']) && count($data['data']) > 0) {
                    return (int) $data['data'][0][2];
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('GLPI findUserByLogin error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Busca un usuario por su email en GLPI.
     */
    public function findUserByEmail(string $email): ?int
    {
        try {
            // Usamos el endpoint de búsqueda avanzada para filtrar por email (Campo 5)
            // Forzamos la visualización del ID (Campo 2) para asegurar que venga en la respuesta
            $response = $this->request('GET', "{$this->baseUrl}/search/User", [
                'query' => [
                    'criteria[0][field]' => 5,
                    'criteria[0][searchtype]' => 'contains',
                    'criteria[0][value]' => $email,
                    'forcedisplay[0]' => 2,
                    'range' => '0-1'
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // El endpoint /search devuelve un objeto con 'data' y 'totalcount'
                if (isset($data['data']) && count($data['data']) > 0) {
                    return (int) $data['data'][0][2]; // El ID suele ser el campo 2
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('GLPI findUserByEmail error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Crea un ticket en GLPI.
     */
    public function createTicket(string $name, string $content, string $priorityLabel = 'Media', ?int $assignTechnicianId = null, ?int $requesterId = null): ?array
    {
        try {
            $map = [
                'Baja'  => ['urgency' => 2, 'impact' => 1],
                'Media' => ['urgency' => 3, 'impact' => 3],
                'Alta'  => ['urgency' => 5, 'impact' => 4],
            ];
            $levels = $map[$priorityLabel] ?? $map['Media'];

            $input = [
                'name'    => $name,
                'content' => $content,
                'urgency' => $levels['urgency'],
                'impact'  => $levels['impact'],
            ];

            // Si se proporciona un técnico, lo asignamos directamente al crear
            if ($assignTechnicianId) {
                $input['_users_id_assign'] = $assignTechnicianId;
            }

            // Si se proporciona un solicitante (requester), lo asignamos
            if ($requesterId) {
                $input['_users_id_requester'] = $requesterId;
            }

            $payload = ['input' => $input];

            $response = $this->request('POST', "{$this->baseUrl}/Ticket", ['json' => $payload]);
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('GLPI createTicket error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Sube un documento a GLPI.
     */
    public function uploadDocument(string $filePath, string $fileName): ?int
    {
        try {
            $sessionToken = $this->getSessionToken();
            if (!$sessionToken) return null;

            $manifest = ['input' => ['name' => "Reporte: {$fileName}", '_filename' => [$fileName]]];
            $response = Http::withHeaders(['App-Token' => $this->appToken, 'Session-Token' => $sessionToken])
            ->attach('filename[0]', file_get_contents($filePath), $fileName)
            ->post("{$this->baseUrl}/Document", ['uploadManifest' => json_encode($manifest)]);

            if ($response->successful()) return $response->json('id');
            Log::warning('GLPI uploadDocument failed', ['body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('GLPI uploadDocument error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Vincula un documento a un ticket.
     */
    public function linkDocumentToTicket(int $documentId, int $ticketId): bool
    {
        try {
            $payload = ['input' => ['documents_id' => $documentId, 'itemtype' => 'Ticket', 'items_id' => $ticketId]];
            $response = $this->request('POST', "{$this->baseUrl}/Document_Item", ['json' => $payload]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('GLPI linkDocumentToTicket error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Vincula un activo a un ticket.
     */
    public function linkBookToTicket(int $glpiBookId, int $ticketId): bool
    {
        try {
            $payload = ['input' => ['itemtype' => $this->bookItemtype, 'items_id' => $glpiBookId, 'tickets_id' => $ticketId]];
            $response = $this->request('POST', "{$this->baseUrl}/Items_Ticket", ['json' => $payload]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('GLPI linkBookToTicket error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Lista tickets de GLPI.
     */
    public function listTickets(int $limit = 20): array
    {
        try {
            $response = $this->request('GET', "{$this->baseUrl}/Ticket", [
                'query' => ['range' => "0-{$limit}", 'sort' => 'date_mod', 'order' => 'DESC']
            ]);
            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Exception $e) {
            Log::error('GLPI listTickets error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Verifica la conexión.
     */
    public function ping(): bool
    {
        return $this->getSessionToken() !== null;
    }
}
