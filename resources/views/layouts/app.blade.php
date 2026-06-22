<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#4f46e5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="ContaMed">
        <link rel="manifest" href="/manifest.json">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-192x192.png">
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-72x72.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

        <!-- Material Symbols Config -->
        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                font-family: 'Material Symbols Outlined';
                vertical-align: middle;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <div class="flex h-screen overflow-hidden">
                @include('layouts.navigation')

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden pt-16 lg:pt-0">
                    <!-- Page Content -->
                    <main class="flex-1 overflow-y-auto bg-gray-50">
                        <!-- Page Heading -->
                        @isset($header)
                            <header class="bg-white shadow z-10">
                                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endisset

                        <!-- Subscription Expiry Warning Banner -->
                        @auth
                            @if(auth()->user()->subscriptionEndingSoon(7))
                                @php
                                    $user = auth()->user();
                                    $empresa = $user->empresa;
                                    $empresaSub = $empresa ? $empresa->subscription('default') : null;
                                    $daysRemaining = $empresaSub?->ends_at ? (int) ceil(now()->diffInDays($empresaSub->ends_at, true)) : null;
                                @endphp
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-0">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                @if($empresa && $daysRemaining !== null)
                                                    La suscripción de {{ $empresa->nombre }} vence en {{ $daysRemaining }} día(s).
                                                @else
                                                    Tu suscripción está por vencer.
                                                @endif
                                                <a href="{{ route('billing.index') }}" class="font-medium underline text-yellow-700 hover:text-yellow-600">Renovar ahora</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endauth

                        <!-- Flash Messages -->
                        <div class="flex-shrink-0">
                            @if (session('success'))
                                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                        <span class="block sm:inline">{{ session('success') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                        <span class="block sm:inline">{{ session('error') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if (session('warning'))
                                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                                        <span class="block sm:inline">{{ session('warning') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        
        <x-ai-chat-widget />
        
        <!-- Scripts Stack -->
        @stack('scripts')
        
        <!-- PWA Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => {
                            console.log('Service Worker registrado con éxito:', registration.scope);
                        })
                        .catch(error => {
                            console.warn('Error al registrar Service Worker:', error);
                        });
                });
            }
            
            // Suprimir errores de extensiones del navegador
            window.addEventListener('error', (event) => {
                if (event.filename && (event.filename.includes('webextension') || event.filename.includes('extension'))) {
                    event.preventDefault();
                    return true;
                }
            });
        </script>
    </body>
</html>
