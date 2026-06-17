<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Comparación de Clínicas
                </h2>
                <p class="text-sm text-gray-600 mt-1">Análisis comparativo de rentabilidad entre dos clínicas</p>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Comparación de Clínicas</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Formulario de Filtros --}}
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                    <div class="p-6">
                        <form method="GET" action="{{ route('reportes.comparacion-clinicas') }}" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {{-- Fecha Inicio --}}
                                <div>
                                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" 
                                           value="{{ $filtros['fecha_inicio'] }}"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- Fecha Fin --}}
                                <div>
                                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" 
                                           value="{{ $filtros['fecha_fin'] }}"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- Clínica 1 --}}
                                <div>
                                    <label for="clinica_1_id" class="block text-sm font-medium text-gray-700 mb-2">Primera Clínica</label>
                                    <select name="clinica_1_id" id="clinica_1_id" 
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Seleccionar clínica</option>
                                        @foreach($clinicas as $clinica)
                                            <option value="{{ $clinica->id }}" 
                                                    {{ $filtros['clinica_1_id'] == $clinica->id ? 'selected' : '' }}>
                                                {{ $clinica->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Clínica 2 --}}
                                <div>
                                    <label for="clinica_2_id" class="block text-sm font-medium text-gray-700 mb-2">Segunda Clínica</label>
                                    <select name="clinica_2_id" id="clinica_2_id" 
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Seleccionar clínica</option>
                                        @foreach($clinicas as $clinica)
                                            <option value="{{ $clinica->id }}" 
                                                    {{ $filtros['clinica_2_id'] == $clinica->id ? 'selected' : '' }}>
                                                {{ $clinica->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105">
                                    Comparar Clínicas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if($datos)
                    {{-- Cards de Comparación --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 mx-4 sm:mx-0">
                        {{-- Clínica 1 --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-6 {{ $datos['ganador'] === 'clinica_1' ? 'ring-4 ring-green-400' : '' }}">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">{{ $datos['clinica_1']->nombre_clinica }}</h3>
                                @if($datos['ganador'] === 'clinica_1')
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">Ganador</span>
                                @endif
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Ingresos</span>
                                    <span class="text-lg font-bold text-green-600">${{ number_format($datos['clinica_1']->total_ingresos, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Gastos</span>
                                    <span class="text-lg font-bold text-red-600">${{ number_format($datos['clinica_1']->total_gastos, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Ganancia Neta</span>
                                    <span class="text-lg font-bold text-blue-600">${{ number_format($datos['clinica_1']->ganancia_neta, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Margen Ganancia</span>
                                    <span class="text-lg font-bold text-purple-600">
                                        {{ $datos['clinica_1']->margen_ganancia !== null ? number_format($datos['clinica_1']->margen_ganancia, 2) . '%' : 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600">Cantidad Repases</span>
                                    <span class="text-lg font-bold text-gray-700">{{ $datos['clinica_1']->cantidad_repases }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Clínica 2 --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-6 {{ $datos['ganador'] === 'clinica_2' ? 'ring-4 ring-green-400' : '' }}">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">{{ $datos['clinica_2']->nombre_clinica }}</h3>
                                @if($datos['ganador'] === 'clinica_2')
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">Ganador</span>
                                @endif
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Ingresos</span>
                                    <span class="text-lg font-bold text-green-600">${{ number_format($datos['clinica_2']->total_ingresos, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Gastos</span>
                                    <span class="text-lg font-bold text-red-600">${{ number_format($datos['clinica_2']->total_gastos, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Ganancia Neta</span>
                                    <span class="text-lg font-bold text-blue-600">${{ number_format($datos['clinica_2']->ganancia_neta, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Margen Ganancia</span>
                                    <span class="text-lg font-bold text-purple-600">
                                        {{ $datos['clinica_2']->margen_ganancia !== null ? number_format($datos['clinica_2']->margen_ganancia, 2) . '%' : 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600">Cantidad Repases</span>
                                    <span class="text-lg font-bold text-gray-700">{{ $datos['clinica_2']->cantidad_repases }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Diferencias --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Diferencias</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-bold text-gray-700">Métrica</th>
                                            <th class="px-6 py-3 text-right font-bold text-gray-700">Diferencia Absoluta</th>
                                            <th class="px-6 py-3 text-right font-bold text-gray-700">Diferencia Porcentual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">Total Ingresos</td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['total_ingresos']['absoluta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ${{ number_format(abs($datos['diferencias']['total_ingresos']['absoluta']), 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['total_ingresos']['absoluta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $datos['diferencias']['total_ingresos']['porcentual'] !== null ? number_format($datos['diferencias']['total_ingresos']['porcentual'], 2) . '%' : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">Total Gastos</td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['total_gastos']['absoluta'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                                ${{ number_format(abs($datos['diferencias']['total_gastos']['absoluta']), 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['total_gastos']['absoluta'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $datos['diferencias']['total_gastos']['porcentual'] !== null ? number_format($datos['diferencias']['total_gastos']['porcentual'], 2) . '%' : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">Ganancia Neta</td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['ganancia_neta']['absoluta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ${{ number_format(abs($datos['diferencias']['ganancia_neta']['absoluta']), 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['ganancia_neta']['absoluta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $datos['diferencias']['ganancia_neta']['porcentual'] !== null ? number_format($datos['diferencias']['ganancia_neta']['porcentual'], 2) . '%' : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">Cantidad Repases</td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['cantidad_repases']['absoluta'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                                                {{ abs($datos['diferencias']['cantidad_repases']['absoluta']) }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold {{ $datos['diferencias']['cantidad_repases']['absoluta'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                                                {{ $datos['diferencias']['cantidad_repases']['porcentual'] !== null ? number_format($datos['diferencias']['cantidad_repases']['porcentual'], 2) . '%' : 'N/A' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
