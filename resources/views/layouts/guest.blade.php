<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-blue-50 via-white to-blue-50 relative overflow-hidden">
            <!-- Patrón de fondo decorativo -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute top-0 left-0 w-96 h-96 bg-blue-600 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-600 rounded-full translate-x-1/2 translate-y-1/2"></div>
                <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-blue-400 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            </div>

            <div class="w-full max-w-md relative z-10">
                <!-- Logo y título -->
                <div class="flex flex-col items-center mb-8">  
                    <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Sistema de Contabilidad</h1>
                    
                </div>

                <!-- Contenedor del formulario -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 sm:p-10 border border-gray-100">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-gray-600">
                    <p>&copy; {{ date('Y') }} Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </body>
</html>
