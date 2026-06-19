<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Bienvenido, {{ auth()->user()->name }}
                </p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Quick Actions -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8 px-4 sm:px-0">
                <a href="{{ route('repases.create') }}" class="group bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl shadow-md border border-gray-100 p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Nuevo Repase</span>
                    </div>
                </a>

                <a href="{{ route('clinicas.create') }}" class="group bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-md border border-gray-100 p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Nueva Clínica</span>
                    </div>
                </a>

                <a href="{{ route('reportes.index') }}" class="group bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-md border border-gray-100 p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Ver Reportes</span>
                    </div>
                </a>

                <a href="{{ route('examenes.index') }}" class="group bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-md border border-gray-100 p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Gestionar Exámenes</span>
                    </div>
                </a>
            </div>

            <!-- KPI Placeholder Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 px-4 sm:px-0">
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-500">Ingresos del Mes</p>
                        <div class="bg-blue-100 rounded-lg p-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">R$ 0,00</p>
                    <p class="text-xs text-gray-400 mt-1">Dashboard KPI — próximo sprint</p>
                </div>

                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-500">Repases del Mes</p>
                        <div class="bg-green-100 rounded-lg p-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">0</p>
                    <p class="text-xs text-gray-400 mt-1">Dashboard KPI — próximo sprint</p>
                </div>

                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-500">Clínicas Activas</p>
                        <div class="bg-purple-100 rounded-lg p-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">0</p>
                    <p class="text-xs text-gray-400 mt-1">Dashboard KPI — próximo sprint</p>
                </div>

                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-500">Tasa de Cobro</p>
                        <div class="bg-amber-100 rounded-lg p-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">0%</p>
                    <p class="text-xs text-gray-400 mt-1">Dashboard KPI — próximo sprint</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
