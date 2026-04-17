<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Publisher;
use App\Services\GlpiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlpiController extends Controller
{
    public function __construct(protected GlpiService $glpiService) {}

    /**
     * Verifica la conectividad con GLPI.
     */
    public function ping(): JsonResponse
    {
        $connected = $this->glpiService->ping();
        return response()->json([
            'connected' => $connected,
            'message'   => $connected ? 'Conexión con GLPI exitosa.' : 'No se pudo conectar con GLPI.',
        ]);
    }

    /**
     * Lista los libros registrados en GLPI.
     */
    public function listBooks(): JsonResponse
    {
        $items = $this->glpiService->listBooks();
        return response()->json($items);
    }
 
    /**
     * Lista los géneros sincronizados localmente.
     */
    public function listGenres(): JsonResponse
    {
        return response()->json(Genre::orderBy('name')->get());
    }
 
    /**
     * Lista las editoriales sincronizadas localmente.
     */
    public function listPublishers(): JsonResponse
    {
        return response()->json(Publisher::orderBy('name')->get());
    }

    /**
     * Lista los tickets de GLPI.
     */
    public function listTickets(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $tickets = $this->glpiService->listTickets($limit);
        return response()->json($tickets);
    }

    /**
     * Sincroniza manualmente un libro de la BD local hacia GLPI.
     */
    public function syncBook(int $bookId): JsonResponse
    {
        $book = \App\Models\Book::find($bookId);

        if (!$book) {
            return response()->json(['message' => 'Libro no encontrado.'], 404);
        }

        if ($book->glpi_id) {
            // Actualizar en GLPI si ya existe
            $success = $this->glpiService->updateBook($book->glpi_id, $book->toArray());
            return response()->json([
                'message' => $success ? 'Libro actualizado en GLPI.' : 'Error al actualizar en GLPI.',
                'glpi_id' => $book->glpi_id,
            ]);
        }

        // Crear nuevo en GLPI
        $result = $this->glpiService->createBook($book->toArray());
        if ($result && isset($result['id'])) {
            $book->update(['glpi_id' => $result['id']]);
            return response()->json([
                'message' => 'Libro sincronizado con GLPI.',
                'glpi_id' => $result['id'],
            ]);
        }

        return response()->json(['message' => 'Error al sincronizar con GLPI.'], 500);
    }

    /**
     * Sincronización Bidireccional Completa (Push & Pull).
     */
    public function syncAll(): JsonResponse
    {
        // Usamos BookService para realizar la lógica compleja
        $bookService = app(\App\Services\BookService::class);
        $results = $bookService->syncFromGlpi();

        return response()->json([
            'message' => 'Proceso de sincronización completado.',
            'details' => $results
        ]);
    }

    /**
     * Reporta un daño en un libro creando un Ticket en GLPI y vinculando evidencias.
     */
    public function createReport(Request $request): JsonResponse
    {
        $request->validate([
            'book_id'     => 'required|exists:books,id',
            'description' => 'required|string|min:10',
            'priority'    => 'required|in:Baja,Media,Alta',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $book = \App\Models\Book::find($request->book_id);
        $imagePath = null;
        $imageFull  = null;

        // 1. Guardar imagen localmente con validación extra
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Verificación extra de que es una imagen real (evitar malware disfrazado)
            if (!@getimagesize($file->getRealPath())) {
                return response()->json(['message' => 'El archivo no es una imagen válida.'], 422);
            }

            // Guardar con nombre aleatorio en storage/app/public/reports
            $imagePath = $file->store('reports', 'public');
            $imageFull = storage_path("app/public/{$imagePath}");
        }

        // 2. Crear Reporte Local
        $report = \App\Models\Report::create([
            'book_id'     => $book->id,
            'user_id'     => auth()->id(),
            'priority'    => $request->priority,
            'description' => $request->description,
            'image_path'  => $imagePath,
        ]);

        // 3. Sincronizar con GLPI
        $glpiTicketId = null;
        if ($book->glpi_id) {
            $user = auth()->user();
            $ticketTitle = "Incidencia: {$book->title}";
            $ticketContent = "Reportado por: {$user->name} ({$user->email})\n" .
                             "Desc: {$request->description}\n" .
                             "Prioridad: {$request->priority}";

            // 3.1 Obtener ID del Técnico asignado (soporte_biblioteca)
            $techLogin = env('GLPI_TECHNICIAN_LOGIN', 'soporte_biblioteca');
            $techId = $this->glpiService->findUserByLogin($techLogin);

            // 3.2 Obtener ID del Solicitante por correo (Librarian)
            $requesterId = $this->glpiService->findUserByEmail($user->email);
            
            // Si lo encontramos y no teníamos su ID, lo guardamos para el futuro
            if ($requesterId && !$user->glpi_user_id) {
                $user->update(['glpi_user_id' => $requesterId]);
            }

            $ticket = $this->glpiService->createTicket($ticketTitle, $ticketContent, $request->priority, $techId, $requesterId);
            
            if ($ticket && isset($ticket['id'])) {
                $glpiTicketId = $ticket['id'];
                $report->update(['glpi_ticket_id' => $glpiTicketId]);

                // Vincular Libro al Ticket
                $this->glpiService->linkBookToTicket($book->glpi_id, $glpiTicketId);

                // Subir y vincular Documento si existe
                if ($imageFull && file_exists($imageFull)) {
                    $docId = $this->glpiService->uploadDocument($imageFull, basename($imagePath));
                    if ($docId) {
                        $this->glpiService->linkDocumentToTicket($docId, $glpiTicketId);
                    }
                }

                // 4. Enviar Correo de Confirmación
                try {
                    $mail = \Illuminate\Support\Facades\Mail::to($user->email);
                    
                    // BCC opcional al administrador si está configurado en env
                    $adminEmail = env('MAIL_ADMIN_REPORT');
                    if ($adminEmail) {
                        $mail->bcc($adminEmail);
                    }

                    $mail->send(new \App\Mail\IncidentReported($report->fresh(['user', 'book'])));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error enviando correo de incidencia', ['error' => $e->getMessage()]);
                }
            }
        }

        // 4. Actualizar estado del libro
        $book->update(['status' => 'Mantenimiento']);

        return response()->json([
            'message'        => 'Incidencia reportada con éxito.',
            'glpi_ticket_id' => $glpiTicketId,
            'report'         => $report
        ]);
    }
}
