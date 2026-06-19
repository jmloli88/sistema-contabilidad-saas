<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} — SaaS</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#1a1a2e">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="ContaMed SaaS">
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
                @include('layouts.saas-navigation')

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden pt-16 lg:pt-0">
                    <!-- Page Content -->
                    <main class="flex-1 overflow-y-auto">
                        <!-- Page Heading -->
                        @isset($header)
                            <header class="bg-white shadow z-10">
                                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endisset

                        <!-- Toast Notifications -->
                        @if(session('success') || session('error') || session('warning'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 translate-y-2"
                             class="fixed top-4 right-4 z-50 max-w-sm">
                            <div class="flex items-center p-4 rounded-2xl shadow-lg border
                                {{ session('success') ? 'bg-green-50 border-green-200 text-green-800' : '' }}
                                {{ session('error') ? 'bg-red-50 border-red-200 text-red-800' : '' }}
                                {{ session('warning') ? 'bg-amber-50 border-amber-200 text-amber-800' : '' }}">
                                <span class="material-symbols-outlined mr-3 text-lg">
                                    {{ session('success') ? 'check_circle' : (session('error') ? 'error' : 'warning') }}
                                </span>
                                <p class="text-sm font-medium flex-1">{{ session('success') ?? session('error') ?? session('warning') }}</p>
                                <button @click="show = false" class="ml-3 text-current opacity-50 hover:opacity-100">
                                    <span class="material-symbols-outlined text-base">close</span>
                                </button>
                            </div>
                        </div>
                        @endif

                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        
        <!-- PWA Install Button -->
        <x-pwa-install-button />
        
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
