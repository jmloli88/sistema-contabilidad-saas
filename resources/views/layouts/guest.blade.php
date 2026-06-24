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

        <style>
            @keyframes gradient {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg) scale(1); opacity: 0.5; }
                25%  { transform: translateY(-40px) translateX(30px) rotate(3deg) scale(1.2); opacity: 0.7; }
                50%  { transform: translateY(-15px) translateX(-15px) rotate(-2deg) scale(0.9); opacity: 0.6; }
                75%  { transform: translateY(-50px) translateX(-30px) rotate(1deg) scale(1.1); opacity: 0.4; }
            }
            @keyframes floatSlow {
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg) scale(1); opacity: 0.4; }
                33%  { transform: translateY(-60px) translateX(-40px) rotate(-4deg) scale(1.3); opacity: 0.6; }
                66%  { transform: translateY(-25px) translateX(40px) rotate(2deg) scale(1.1); opacity: 0.5; }
            }
            @keyframes floatReverse {
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg) scale(1); opacity: 0.45; }
                40%  { transform: translateY(50px) translateX(-35px) rotate(5deg) scale(1.25); opacity: 0.65; }
                70%  { transform: translateY(20px) translateX(30px) rotate(-2deg) scale(1.05); opacity: 0.5; }
            }
            .bg-animated {
                background: linear-gradient(-45deg, #0c4a6e, #0891b2, #06b6d4, #22d3ee, #0e7490, #06b6d4);
                background-size: 300% 300%;
                animation: gradient 8s ease infinite;
            }
            .floating-shape {
                position: absolute;
                border-radius: 9999px;
                filter: blur(40px);
                pointer-events: none;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-animated relative overflow-hidden flex items-center justify-center p-4 sm:p-6">
            {{-- Floating decorative shapes --}}
            <div class="floating-shape w-[500px] h-[500px] bg-cyan-400/25 -top-20 -left-40" style="animation: float 8s ease-in-out infinite;"></div>
            <div class="floating-shape w-[400px] h-[400px] bg-blue-500/20 bottom-0 -right-20" style="animation: floatSlow 10s ease-in-out infinite 2s;"></div>
            <div class="floating-shape w-[350px] h-[350px] bg-cyan-300/20 top-1/3 right-1/4" style="animation: floatReverse 9s ease-in-out infinite 4s;"></div>
            <div class="floating-shape w-[300px] h-[300px] bg-sky-400/15 bottom-10 left-20" style="animation: float 11s ease-in-out infinite 1s;"></div>

            {{-- Main card --}}
            <div class="relative z-10 w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
