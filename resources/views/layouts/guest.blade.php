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
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); opacity: 0.4; }
                25%  { transform: translateY(-30px) translateX(20px) rotate(3deg); opacity: 0.6; }
                50%  { transform: translateY(-10px) translateX(-10px) rotate(-2deg); opacity: 0.5; }
                75%  { transform: translateY(-40px) translateX(-20px) rotate(1deg); opacity: 0.3; }
            }
            @keyframes floatSlow {
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); opacity: 0.3; }
                33%  { transform: translateY(-50px) translateX(-30px) rotate(-3deg); opacity: 0.5; }
                66%  { transform: translateY(-20px) translateX(30px) rotate(2deg); opacity: 0.4; }
            }
            @keyframes floatReverse {
                0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); opacity: 0.35; }
                40%  { transform: translateY(40px) translateX(-25px) rotate(4deg); opacity: 0.55; }
                70%  { transform: translateY(15px) translateX(20px) rotate(-1deg); opacity: 0.45; }
            }
            .bg-animated {
                background: linear-gradient(-45deg, #0c4a6e, #0891b2, #06b6d4, #155e75, #0e7490);
                background-size: 400% 400%;
                animation: gradient 15s ease infinite;
            }
            .floating-shape {
                position: absolute;
                border-radius: 9999px;
                filter: blur(60px);
                pointer-events: none;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-animated relative overflow-hidden flex items-center justify-center p-4 sm:p-6">
            {{-- Floating decorative shapes --}}
            <div class="floating-shape w-96 h-96 bg-cyan-400/20 top-10 -left-20" style="animation: float 12s ease-in-out infinite;"></div>
            <div class="floating-shape w-80 h-80 bg-blue-500/20 bottom-20 -right-20" style="animation: floatSlow 16s ease-in-out infinite 2s;"></div>
            <div class="floating-shape w-72 h-72 bg-cyan-300/15 top-1/2 left-1/3" style="animation: floatReverse 14s ease-in-out infinite 4s;"></div>
            <div class="floating-shape w-64 h-64 bg-blue-400/15 bottom-10 left-10" style="animation: float 18s ease-in-out infinite 1s;"></div>

            {{-- Main card --}}
            <div class="relative z-10 w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
