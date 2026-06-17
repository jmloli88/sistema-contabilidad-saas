<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($request->is('billing*', 'profile*', 'verify-email*', 'confirm-password*', 'logout*')) {
            return $next($request);
        }

        // Phase 3: Dual-path — empresa subscription first, clinic-shared fallback
        if ($user->empresa_id && $user->empresa && $user->empresa->hasActiveSubscription()) {
            \Log::info('Subscription check: empresa path used', [
                'user_id' => $user->id,
                'empresa_id' => $user->empresa_id,
            ]);
            return $next($request);
        }

        // Fallback: clinic-shared path (transitional, will be removed in Phase 5)
        if ($user->hasActiveSubscriptionInClinic()) {
            \Log::info('Subscription check: fallback path used', [
                'user_id' => $user->id,
                'path' => 'clinic-shared-fallback',
            ]);
            return $next($request);
        }

        return redirect('/billing');
    }
}
