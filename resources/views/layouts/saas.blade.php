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
                        <div x-data="toaster" class="fixed top-4 right-4 z-[9999] space-y-2 w-80 max-w-[calc(100vw-2rem)] pointer-events-none">
                            <template x-for="toast in toasts" :key="toast.id">
                                <div x-show="toast.visible"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-x-4"
                                     x-transition:enter-end="opacity-100 translate-x-0"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-x-0"
                                     x-transition:leave-end="opacity-0 translate-x-4"
                                     :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 
                                             toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                                             'bg-amber-50 border-amber-200 text-amber-800'"
                                     class="flex items-center gap-2 px-4 py-3 rounded-xl border shadow-lg text-sm pointer-events-auto">
                                    <span class="material-symbols-outlined text-base" 
                                          x-text="toast.type === 'success' ? 'check_circle' : toast.type === 'error' ? 'error' : 'warning'"></span>
                                    <span class="flex-1" x-text="toast.message"></span>
                                    <button @click="dismiss(toast.id)" class="text-current opacity-50 hover:opacity-100 shrink-0">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <script>
                            document.addEventListener('alpine:init', () => {
                                Alpine.data('toaster', () => ({
                                    toasts: [],
                                    init() {
                                        const flashData = document.getElementById('flash-data');
                                        if (flashData) {
                                            try {
                                                const data = JSON.parse(flashData.textContent);
                                                data.forEach(d => this.show(d.message, d.type));
                                            } catch(e) {}
                                            flashData.remove();
                                        }
                                        window.addEventListener('toast', e => this.show(e.detail.message, e.detail.type));
                                    },
                                    show(message, type = 'success') {
                                        const id = Date.now() + Math.random();
                                        this.toasts.push({ id, message, type, visible: true });
                                        setTimeout(() => this.dismiss(id), 4000);
                                    },
                                    dismiss(id) {
                                        const t = this.toasts.find(t => t.id === id);
                                        if (t) t.visible = false;
                                        setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
                                    }
                                }));
                            });
                        </script>

                        <div id="flash-data" class="hidden">@json(array_filter([
                            session('success') ? ['message' => session('success'), 'type' => 'success'] : null,
                            session('error') ? ['message' => session('error'), 'type' => 'error'] : null,
                            session('warning') ? ['message' => session('warning'), 'type' => 'warning'] : null,
                        ]))</div>

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
