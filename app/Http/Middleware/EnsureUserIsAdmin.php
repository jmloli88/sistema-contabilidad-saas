<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('Admin Middleware Check', [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role,
            'is_admin' => auth()->user()?->isAdmin(),
            'url' => $request->url()
        ]);
        
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            \Log::warning('Admin access denied', [
                'user_id' => auth()->id(),
                'url' => $request->url()
            ]);
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
        
        return $next($request);
    }
}
