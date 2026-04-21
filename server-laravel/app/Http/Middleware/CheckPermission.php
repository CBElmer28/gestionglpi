<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Maneja la autorización basada en permisos específicos.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Si no hay usuario o no tiene el permiso requerido, bloqueamos.
        if (!$user || !$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'No tienes los permisos necesarios para acceder a este recurso.',
                'error'   => 'Forbidden',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
