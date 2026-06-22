<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'prevent.duplicate.submissions' => \App\Http\Middleware\PreventDuplicateSubmissions::class,
            'subscription' => \App\Http\Middleware\EnsureSubscriptionIsActive::class,
            'premium' => \App\Http\Middleware\EnsurePremiumSubscription::class,
            'empresa.scope' => \App\Http\Middleware\ScopeByEmpresa::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'telegram/webhook',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('saas/*')) {
                return route('saas.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
