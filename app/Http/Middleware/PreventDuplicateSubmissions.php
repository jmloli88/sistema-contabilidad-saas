<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para prevenir envíos duplicados de formularios
 * 
 * Este middleware genera un token único por formulario y lo valida
 * en el servidor para prevenir envíos duplicados, especialmente útil
 * en navegadores como Safari que pueden tener problemas con doble clic.
 */
class PreventDuplicateSubmissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a peticiones POST, PUT, PATCH
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Obtener el token de envío único del request
        $submissionToken = $request->input('_submission_token');
        
        if (!$submissionToken) {
            // Si no hay token, permitir el request (para compatibilidad con formularios antiguos)
            return $next($request);
        }

        // Crear una clave única para este token
        $cacheKey = "submission_token:{$submissionToken}";
        
        // Verificar si este token ya fue usado
        if (Cache::has($cacheKey)) {
            // Token ya fue usado, esto es un envío duplicado
            return redirect()
                ->back()
                ->with('error', 'Este formulario ya fue enviado. Por favor no haga doble clic.')
                ->withInput();
        }

        // Marcar el token como usado por 5 minutos
        // Esto previene envíos duplicados pero permite reintentos después de 5 minutos
        Cache::put($cacheKey, true, now()->addMinutes(5));

        return $next($request);
    }
}
