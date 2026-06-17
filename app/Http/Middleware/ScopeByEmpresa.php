<?php

namespace App\Http\Middleware;

use App\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeByEmpresa
{
    /**
     * Handle an incoming request.
     *
     * Sets the EmpresaContext from the authenticated user's empresa_id.
     * This must run after the 'auth' middleware so the user is available.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->empresa_id) {
            EmpresaContext::set((int) $user->empresa_id);
        }

        return $next($request);
    }
}
