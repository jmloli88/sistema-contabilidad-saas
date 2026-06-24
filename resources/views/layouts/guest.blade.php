<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">
            {{-- Left: Branding panel (hidden on mobile) --}}
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-900">
                {{-- Decorative circles --}}
                <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-indigo-500/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-32 w-80 h-80 rounded-full bg-indigo-400/20 blur-3xl"></div>
                <div class="absolute top-1/3 right-1/4 w-64 h-64 rounded-full bg-white/5 blur-3xl"></div>

                {{-- Subtle grid pattern --}}
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"></div>

                {{-- Brand content --}}
                <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 w-full">
                    <div class="mb-4">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20 mb-6">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 1 1-1.5 0V3A.75.75 0 0 1 12 2.25Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl xl:text-4xl font-bold text-white tracking-tight mb-3">VictCorp</h1>
                        <h2 class="text-lg xl:text-xl text-indigo-200 font-medium">Sistema de Contabilidad Médica</h2>
                        <p class="mt-4 text-indigo-300/80 text-sm leading-relaxed max-w-sm">
                            Gestión de repases, clínicas, exámenes y reportes financieros para tu empresa.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right: Form panel --}}
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 bg-white">
                {{-- Mobile branding (visible only on small screens) --}}
                <div class="lg:hidden absolute top-0 left-0 right-0 bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-8">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 1 1-1.5 0V3A.75.75 0 0 1 12 2.25Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white">VictCorp</h1>
                            <p class="text-xs text-indigo-200">Sistema de Contabilidad Médica</p>
                        </div>
                    </div>
                </div>

                <div class="w-full max-w-md mx-auto lg:pt-0 pt-28 sm:pt-32">
                    <div class="mb-8 lg:hidden"></div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
