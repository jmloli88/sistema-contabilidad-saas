<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Reporte Comparativo de Períodos
                </h2>
                <p class="text-sm text-gray-600 mt-1">Análisis comparativo de métricas entre diferentes períodos temporales</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                {{-- Breadcrumbs --}}
                <nav class="flex mb-8 mx-4 sm:mx-0" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="{{ route('reportes.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 transition-colors duration-200">Reportes</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Reporte Comparativo</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Filtros Personalizados (4 fechas) --}}
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                    <div class="p-4 sm:p-6 md:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Seleccionar Períodos</h3>
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Comparar dos períodos</span>
                        </div>
                        
                        <form method="GET" action="{{ route('reportes.comparativo') }}" class="space-y-6">
                            {{-- Período Anterior --}}
                            <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl p-5 border-2 border-orange-200">
                                <h4 class="text-sm font-bold text-orange-900 mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    Período Anterior
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label for="fecha_inicio_anterior" class="block text-sm font-semibold text-gray-700">
                                            Fecha Inicio
                                        </label>
                                        <input 
                                            type="date" 
                                            name="fecha_inicio_anterior" 
                                            id="fecha_inicio_anterior" 
                                            value="{{ $filtros['fecha_inicio_anterior'] ?? '' }}" 
                                            required
                                            class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                                        >
                                    </div>
                                    <div class="space-y-2">
                                        <label for="fecha_fin_anterior" class="block text-sm font-semibold text-gray-700">
                                            Fecha Fin
                                        </label>
                                        <input 
                                            type="date" 
                                            name="fecha_fin_anterior" 
                                            id="fecha_fin_anterior" 
                                            value="{{ $filtros['fecha_fin_anterior'] ?? '' }}" 
                                            required
                                            min="{{ $filtros['fecha_inicio_anterior'] ?? '' }}"
                                            class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Período Actual --}}
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-5 border-2 border-green-200">
                                <h4 class="text-sm font-bold text-green-900 mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Período Actual
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label for="fecha_inicio_actual" class="block text-sm font-semibold text-gray-700">
                                            Fecha Inicio
                                        </label>
                                        <input 
                                            type="date" 
                                            name="fecha_inicio_actual" 
                                            id="fecha_inicio_actual" 
                                            value="{{ $filtros['fecha_inicio_actual'] ?? '' }}" 
                                            required
                                            class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                                        >
                                    </div>
                                    <div class="space-y-2">
                                        <label for="fecha_fin_actual" class="block text-sm font-semibold text-gray-700">
                                            Fecha Fin
                                        </label>
                                        <input 
                                            type="date" 
                                            name="fecha_fin_actual" 
                                            id="fecha_fin_actual" 
                                            value="{{ $filtros['fecha_fin_actual'] ?? '' }}" 
                                            required
                                            min="{{ $filtros['fecha_inicio_actual'] ?? '' }}"
                                            class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Clínica --}}
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Clínica (Opcional)
                                    </span>
                                </label>
                                <select 
                                    name="clinica_id" 
                                    id="clinica_id" 
                                    class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                                >
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ ($filtros['clinica_id'] ?? '') == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Botones --}}
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button type="submit" class="w-full sm:w-auto text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus:ring-4 focus:ring-cyan-200 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                    Comparar Períodos
                                </button>
                                <a href="{{ route('reportes.comparativo') }}" class="w-full sm:w-auto text-center text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 hover:border-gray-400">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Limpiar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Mensaje de datos vacíos --}}
                @if(empty($datos))
                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-6 mx-4 sm:mx-0 mb-8">
                        <div class="flex items-center space-x-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="text-lg font-bold text-yellow-900">No se encontraron datos</h4>
                                <p class="text-sm text-yellow-700">Selecciona dos períodos para comparar las métricas financieras.</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Tabla de Comparación --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Comparación de Períodos</h3>
                                </div>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm text-left text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Métrica</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">
                                                <div class="flex flex-col items-end">
                                                    <span class="text-orange-600">Período Anterior</span>
                                                    <span class="text-xs font-normal text-gray-500 mt-1">
                                                        {{ \Carbon\Carbon::parse($datos['periodo_anterior']['fecha_inicio'])->format('d/m/Y') }} - 
                                                        {{ \Carbon\Carbon::parse($datos['periodo_anterior']['fecha_fin'])->format('d/m/Y') }}
                                                    </span>
                                                </div>
                                            </th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">
                                                <div class="flex flex-col items-end">
                                                    <span class="text-green-600">Período Actual</span>
                                                    <span class="text-xs font-normal text-gray-500 mt-1">
                                                        {{ \Carbon\Carbon::parse($datos['periodo_actual']['fecha_inicio'])->format('d/m/Y') }} - 
                                                        {{ \Carbon\Carbon::parse($datos['periodo_actual']['fecha_fin'])->format('d/m/Y') }}
                                                    </span>
                                                </div>
                                            </th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Variación %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Total Ingresos --}}
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                Total Ingresos
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                ${{ number_format($datos['periodo_anterior']['total_ingresos'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                ${{ number_format($datos['periodo_actual']['total_ingresos'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-center whitespace-nowrap">
                                                @if($datos['variaciones']['ingresos_variacion'] !== null)
                                                    <span class="font-bold text-base {{ $datos['variaciones']['ingresos_variacion'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $datos['variaciones']['ingresos_variacion'] >= 0 ? '+' : '' }}{{ number_format($datos['variaciones']['ingresos_variacion'], 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 font-medium">N/A</span>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Total Gastos --}}
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                Total Gastos
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                ${{ number_format($datos['periodo_anterior']['total_gastos'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                ${{ number_format($datos['periodo_actual']['total_gastos'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-center whitespace-nowrap">
                                                @if($datos['variaciones']['gastos_variacion'] !== null)
                                                    <span class="font-bold text-base {{ $datos['variaciones']['gastos_variacion'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $datos['variaciones']['gastos_variacion'] >= 0 ? '+' : '' }}{{ number_format($datos['variaciones']['gastos_variacion'], 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 font-medium">N/A</span>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Ganancia Neta --}}
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                Ganancia Neta
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-bold {{ $datos['periodo_anterior']['ganancia_neta'] >= 0 ? 'text-green-700' : 'text-red-700' }} whitespace-nowrap">
                                                ${{ number_format($datos['periodo_anterior']['ganancia_neta'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-bold {{ $datos['periodo_actual']['ganancia_neta'] >= 0 ? 'text-green-700' : 'text-red-700' }} whitespace-nowrap">
                                                ${{ number_format($datos['periodo_actual']['ganancia_neta'], 2) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 text-center whitespace-nowrap">
                                                @if($datos['variaciones']['ganancia_variacion'] !== null)
                                                    <span class="font-bold text-base {{ $datos['variaciones']['ganancia_variacion'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $datos['variaciones']['ganancia_variacion'] >= 0 ? '+' : '' }}{{ number_format($datos['variaciones']['ganancia_variacion'], 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 font-medium">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Gráfico de Líneas de Tendencia --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Tendencia de Ingresos y Gastos</h3>
                                </div>
                            </div>
                            
                            <div class="relative w-full" style="height: 300px; min-height: 300px;">
                                <canvas id="chartComparativo"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Botones de Exportación --}}
                    @include('reportes.partials.botones-exportacion', [
                        'tipo' => 'comparativo',
                        'filtros' => $filtros
                    ])
                @endif

            </div>
        </div>
    </div>

    @if(!empty($datos))
        {{-- Chart.js Script --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('chartComparativo');
                
                // Preparar datos para el gráfico de líneas
                const labels = ['Período Anterior', 'Período Actual'];
                const ingresosData = [
                    {{ $datos['periodo_anterior']['total_ingresos'] }},
                    {{ $datos['periodo_actual']['total_ingresos'] }}
                ];
                const gastosData = [
                    {{ $datos['periodo_anterior']['total_gastos'] }},
                    {{ $datos['periodo_actual']['total_gastos'] }}
                ];
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Ingresos ($)',
                                data: ingresosData,
                                borderColor: 'rgba(34, 197, 94, 1)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 6,
                                pointHoverRadius: 8,
                                pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                            },
                            {
                                label: 'Gastos ($)',
                                data: gastosData,
                                borderColor: 'rgba(239, 68, 68, 1)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 6,
                                pointHoverRadius: 8,
                                pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': $';
                                        }
                                        label += context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    callback: function(value) {
                                        return '$' + value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
