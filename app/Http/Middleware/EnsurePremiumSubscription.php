<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumSubscription
{
    /**
     * Block access if the authenticated user's empresa does not have
     * an active PREMIUM subscription. Standard-tier users are denied.
     *
     * Whitelisted paths: billing, profile, and auth-related routes
     * so the admin can still upgrade from the billing page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        if ($request->is('billing*', 'profile*', 'verify-email*', 'confirm-password*', 'logout*')) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->empresa_id && $user->empresa && $user->empresa->hasPremium()) {
            return $next($request);
        }

        // No premium — redirect to billing so they can upgrade
        return redirect('/billing')->with('warning', 'Esta funcionalidad requiere la suscripción PREMIUM.');
    }
}
