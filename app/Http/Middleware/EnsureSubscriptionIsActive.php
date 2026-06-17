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

        if ($user->hasActiveSubscriptionInClinic()) {
            return $next($request);
        }

        return redirect('/billing');
    }
}
