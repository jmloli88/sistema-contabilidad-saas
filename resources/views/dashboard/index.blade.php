<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-cyan-600 text-xl fill">dashboard</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Dashboard Financiero
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Resumen de tu actividad médica</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <!-- Fondo con degradado sutil -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros con diseño moderno -->
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Filtros</h3>
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Personaliza tu vista</span>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Clínica
                                    </span>
                                </label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ $filters['clinica_id'] == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Estado -->
                            <div class="space-y-2">
                                <label for="estado" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Estado
                                    </span>
                                </label>
                                <select name="estado" id="estado" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" {{ $filters['estado'] == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="pagado" {{ $filters['estado'] == 'pagado' ? 'selected' : '' }}>Pagado</option>
                                </select>
                            </div>

                            <!-- Filtro por Fecha Desde -->
                            <div class="space-y-2">
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Desde
                                    </span>
                                </label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                            </div>

                            <!-- Filtro por Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Hasta
                                    </span>
                                </label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                            </div>

                            <!-- Botones -->
                            <div class="md:col-span-2 lg:col-span-4 flex flex-wrap gap-3 pt-2">
                                <button type="submit" class="flex-1 sm:flex-none text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                    Aplicar Filtros
                                </button>
                                <a href="{{ route('dashboard') }}" class="flex-1 sm:flex-none text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 hover:border-gray-400">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cards de Métricas con Degradados Coloridos y Animaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-8 px-4 sm:px-0">
                    <!-- Card Total Ingresos - Degradado Cyan/Blue -->
                    <div class="group bg-gradient-to-br from-cyan-400 to-blue-500 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="p-6 relative overflow-hidden">
                            <!-- Patrón decorativo de fondo -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                            
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-white text-xs font-semibold mb-3 opacity-90 uppercase tracking-wider">Ingresos Totales</p>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white break-words">
                                        R${{ number_format($metrics['total_ingresos'], 2) }}
                                    </h3>
                                </div>
                                <div class="flex sm:hidden flex-shrink-0 bg-white bg-opacity-25 backdrop-blur-sm rounded-2xl p-3 group-hover:bg-opacity-30 transition-all duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Total Gastos - Degradado Rojo/Naranja -->
                    <div class="group bg-gradient-to-br from-red-400 to-orange-500 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                            
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-white text-xs font-semibold mb-3 opacity-90 uppercase tracking-wider">Gastos Totales</p>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white break-words">
                                        R${{ number_format($metrics['total_gastos'], 2) }}
                                    </h3>
                                </div>
                                <div class="flex sm:hidden flex-shrink-0 bg-white bg-opacity-25 backdrop-blur-sm rounded-2xl p-3 group-hover:bg-opacity-30 transition-all duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Total Neto - Degradado Azul -->
                    <div class="group bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                            
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-white text-xs font-semibold mb-3 opacity-90 uppercase tracking-wider">Balance Neto</p>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white break-words">
                                        R${{ number_format($metrics['total_neto'], 2) }}
                                    </h3>
                                </div>
                                <div class="flex sm:hidden flex-shrink-0 bg-white bg-opacity-25 backdrop-blur-sm rounded-2xl p-3 group-hover:bg-opacity-30 transition-all duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Total Pendiente - Degradado Morado -->
                    <div class="group bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                            
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-white text-xs font-semibold mb-3 opacity-90 uppercase tracking-wider">Por Cobrar</p>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white break-words">
                                        R${{ number_format($metrics['total_pendiente'], 2) }}
                                    </h3>
                                </div>
                                <div class="flex sm:hidden flex-shrink-0 bg-white bg-opacity-25 backdrop-blur-sm rounded-2xl p-3 group-hover:bg-opacity-30 transition-all duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Total Pagado - Degradado Verde -->
                    <div class="group bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                        <div class="p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-12 -mb-12"></div>
                            
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-white text-xs font-semibold mb-3 opacity-90 uppercase tracking-wider">Cobrado</p>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white break-words">
                                        R${{ number_format($metrics['total_pagado'], 2) }}
                                    </h3>
                                </div>
                                <div class="flex sm:hidden flex-shrink-0 bg-white bg-opacity-25 backdrop-blur-sm rounded-2xl p-3 group-hover:bg-opacity-30 transition-all duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos con diseño moderno -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 px-4 sm:px-0">
                    <!-- Gráfico Ingresos vs Gastos -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Ingresos vs Gastos</h3>
                                </div>
                                <button onclick="openChartModal('ingresosVsGastosChart', 'Ingresos vs Gastos')" class="text-gray-400 hover:text-blue-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="ingresosVsGastosChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Totales por Clínica -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Totales por Clínica</h3>
                                </div>
                                <button onclick="openChartModal('totalesPorClinicaChart', 'Totales por Clínica')" class="text-gray-400 hover:text-purple-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="totalesPorClinicaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Pagados vs Pendientes -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Pagados vs Pendientes</h3>
                                </div>
                                <button onclick="openChartModal('pagadosVsPendientesChart', 'Pagados vs Pendientes')" class="text-gray-400 hover:text-green-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64 flex items-center justify-center">
                                <div class="w-48">
                                    <canvas id="pagadosVsPendientesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Desglose de Gastos por Categoría -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-red-500 to-orange-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Gastos por Categoría</h3>
                                </div>
                                <button onclick="openChartModal('gastosPorCategoriaChart', 'Gastos por Categoría')" class="text-gray-400 hover:text-red-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="gastosPorCategoriaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Top 5 Exámenes Más Rentables -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-indigo-500 to-blue-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Top 5 Exámenes</h3>
                                </div>
                                <button onclick="openChartModal('topExamenesChart', 'Top 5 Exámenes Más Rentables')" class="text-gray-400 hover:text-indigo-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="topExamenesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Evolución de Ingresos Netos -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-cyan-500 to-teal-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Evolución Ingresos Netos</h3>
                                </div>
                                <button onclick="openChartModal('evolucionIngresosNetosChart', 'Evolución de Ingresos Netos')" class="text-gray-400 hover:text-cyan-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="evolucionIngresosNetosChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Días Promedio de Cobro por Clínica -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-yellow-500 to-amber-500 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Días Promedio de Cobro</h3>
                                </div>
                                <button onclick="openChartModal('diasCobroPorClinicaChart', 'Días Promedio de Cobro por Clínica')" class="text-gray-400 hover:text-yellow-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="diasCobroPorClinicaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Margen de Ganancia por Clínica -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Margen de Ganancia (%)</h3>
                                </div>
                                <button onclick="openChartModal('margenGananciaPorClinicaChart', 'Margen de Ganancia por Clínica')" class="text-gray-400 hover:text-emerald-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="margenGananciaPorClinicaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Top 5 Clínicas con Mayor Cantidad de Consultas -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg p-2">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">Top 5 Clínicas - Consultas</h3>
                                </div>
                                <button onclick="openChartModal('topClinicasConsultasChart', 'Top 5 Clínicas con Mayor Cantidad de Consultas')" class="text-gray-400 hover:text-indigo-600 transition-colors duration-200" title="Expandir gráfico">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="relative h-64">
                                <canvas id="topClinicasConsultasChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para Chart.js -->
    <script>
        // Datos para los gráficos pasados desde el controlador
        const ingresosVsGastosData = @json($ingresosVsGastosChart);
        const totalesPorClinicaData = @json($totalesPorClinicaChart);
        const pagadosVsPendientesData = @json($pagadosVsPendientesChart);
        const gastosPorCategoriaData = @json($gastosPorCategoriaChart);
        const topExamenesData = @json($topExamenesChart);
        const evolucionIngresosNetosData = @json($evolucionIngresosNetosChart);
        const diasCobroPorClinicaData = @json($diasCobroPorClinicaChart);
        const margenGananciaPorClinicaData = @json($margenGananciaPorClinicaChart);
        const topClinicasConsultasData = @json($topClinicasConsultasChart);
    </script>

    <!-- Modal para gráficos expandidos -->
    <div id="chartModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header del modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 flex-shrink-0">
                <h3 id="modalChartTitle" class="text-xl font-bold text-gray-900"></h3>
                <button onclick="closeChartModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-lg p-2 transition-all duration-200">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Contenido del modal -->
            <div class="p-8 overflow-auto flex-1">
                <div class="w-full h-full flex items-center justify-center">
                    <div class="w-full" style="max-height: 500px; height: 500px;">
                        <canvas id="modalChartCanvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @vite(['resources/js/dashboard-charts.js'])
    @endpush
</x-app-layout>
