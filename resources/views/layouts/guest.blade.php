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
            @keyframes wave1 {
                0%, 100% { transform: translateX(0) translateY(0) scaleY(1); }
                25%  { transform: translateX(5%) translateY(-8px) scaleY(1.15); }
                50%  { transform: translateX(10%) translateY(0) scaleY(0.9); }
                75%  { transform: translateX(5%) translateY(6px) scaleY(1.1); }
            }
            @keyframes wave2 {
                0%, 100% { transform: translateX(0) translateY(0) scaleY(1); }
                33%  { transform: translateX(-8%) translateY(-6px) scaleY(0.85); }
                66%  { transform: translateX(-4%) translateY(10px) scaleY(1.2); }
            }
            @keyframes wave3 {
                0%, 100% { transform: translateX(0) translateY(0) scaleY(1); }
                50%  { transform: translateX(-6%) translateY(5px) scaleY(1.1); }
            }
            .bg-animated {
                background: linear-gradient(-45deg, #0a1628, #112240, #1a2a4a, #1e3a5f, #152238, #0f1a2e);
                background-size: 300% 300%;
                animation: gradient 10s ease infinite;
            }
            .wave {
                position: absolute;
                left: -10%;
                right: -10%;
                pointer-events: none;
            }
            .wave svg {
                display: block;
                width: 120%;
                height: 100%;
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
            {{-- Wave layers — positioned at mid-height --}}
            <div class="wave bottom-32 h-64 opacity-30" style="animation: wave1 12s ease-in-out infinite;">
                <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path fill="#1e3a5f" d="M0,160 C120,260 240,60 480,160 C720,260 840,100 960,180 C1080,260 1200,100 1320,200 L1440,160 L1440,320 L0,320 Z"/>
                </svg>
            </div>
            <div class="wave bottom-28 h-56 opacity-20" style="animation: wave2 14s ease-in-out infinite 1s;">
                <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path fill="#162d50" d="M0,224 C96,128 192,96 288,160 C480,288 576,128 768,192 C960,256 1056,160 1152,192 C1296,224 1392,160 1440,128 L1440,320 L0,320 Z"/>
                </svg>
            </div>
            <div class="wave bottom-24 h-48 opacity-15" style="animation: wave3 16s ease-in-out infinite 3s;">
                <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path fill="#0f2440" d="M0,192 C240,288 384,128 576,160 C816,192 960,256 1200,192 C1320,160 1380,192 1440,224 L1440,320 L0,320 Z"/>
                </svg>
            </div>

            {{-- Top floating shapes for depth --}}
            <div class="floating-shape w-80 h-80 bg-blue-950/20 -top-20 -right-20" style="animation: wave2 18s ease-in-out infinite;"></div>
            <div class="floating-shape w-64 h-64 bg-indigo-950/15 top-1/4 -left-20" style="animation: wave1 14s ease-in-out infinite 5s;"></div>

            {{-- Main card --}}
            <div class="relative z-10 w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
