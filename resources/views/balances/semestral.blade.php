<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Balance Semestral</h2>
                <p class="text-sm text-gray-600 mt-1">Resumen financiero por semestre</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <nav class="flex mb-8 mx-4 sm:mx-0" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors duration-200"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>Dashboard</a>
                        </li>
                        <li><div class="flex items-center"><svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg><a href="{{ route('balances.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 transition-colors duration-200">Balances</a></div></li>
                        <li><div class="flex items-center"><svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg><span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Semestral</span></div></li>
                    </ol>
                </nav>

                @include('balances.partials.filtros', ['route' => route('balances.semestral'), 'filtros' => $filtros, 'clinicas' => $clinicas])

                @if($balances->isEmpty())
                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-6 mx-4 sm:mx-0 mb-8">
                        <div class="flex items-center space-x-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            <div><h4 class="text-lg font-bold text-yellow-900">No se encontraron datos</h4><p class="text-sm text-yellow-700">No hay datos disponibles para los filtros seleccionados.</p></div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 mx-4 sm:mx-0">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6"><p class="text-sm font-medium text-gray-500 mb-1">Total Ingresos</p><p class="text-2xl font-bold text-green-600">R$ {{ number_format($resumen['total_ingresos'], 2) }}</p></div>
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6"><p class="text-sm font-medium text-gray-500 mb-1">Total Gastos</p><p class="text-2xl font-bold text-red-600">R$ {{ number_format($resumen['total_gastos'], 2) }}</p></div>
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6"><p class="text-sm font-medium text-gray-500 mb-1">Total Neto</p><p class="text-2xl font-bold text-gray-900">R$ {{ number_format($resumen['total_neto'], 2) }}</p></div>
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6"><p class="text-sm font-medium text-gray-500 mb-1">Margen Promedio</p><p class="text-2xl font-bold {{ $resumen['margen_ganancia'] !== null && $resumen['margen_ganancia'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $resumen['margen_ganancia'] !== null ? number_format($resumen['margen_ganancia'], 1) . '%' : 'N/A' }}</p></div>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900">Balance Semestral</h3>
                                </div>
                                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ $balances->count() }} semestres</span>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm text-left text-gray-700">
                                            <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                                <tr>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Semestre</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Ingresos</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Gastos</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Neto</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Margen</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Repases</th>
                                                    <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php $totalIngresos = 0; $totalGastos = 0; $totalNeto = 0; $totalRepases = 0; @endphp
                                                @foreach($balances as $balance)
                                                @php
                                                    $totalIngresos += $balance->total_ingresos;
                                                    $totalGastos += $balance->total_gastos;
                                                    $totalNeto += $balance->total_neto;
                                                    $totalRepases += $balance->total_repases;
                                                    $parts = explode('-S', $balance->period);
                                                @endphp
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $balance->period_label }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right text-green-600 font-medium whitespace-nowrap">R$ {{ number_format($balance->total_ingresos, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right text-red-600 font-medium whitespace-nowrap">R$ {{ number_format($balance->total_gastos, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right font-semibold whitespace-nowrap {{ $balance->total_neto >= 0 ? 'text-gray-900' : 'text-red-600' }}">R$ {{ number_format($balance->total_neto, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap {{ $balance->margen_ganancia !== null && $balance->margen_ganancia >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $balance->margen_ganancia !== null ? number_format($balance->margen_ganancia, 1) . '%' : 'N/A' }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right font-medium whitespace-nowrap">{{ $balance->total_repases }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                                        <a href="{{ route('balances.detalle', ['periodo' => 'semestral', 'anio' => $parts[0] ?? date('Y'), 'periodo_index' => $parts[1] ?? '1', 'clinica_id' => $filtros['clinica_id'], 'estado' => $filtros['estado']]) }}" class="inline-flex items-center px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-xs font-medium">Ver</a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray-50 font-semibold">
                                                <tr>
                                                    <td class="px-4 sm:px-6 py-4 text-gray-900 font-bold">Total</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right text-green-700">R$ {{ number_format($totalIngresos, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right text-red-700">R$ {{ number_format($totalGastos, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right text-gray-900">R$ {{ number_format($totalNeto, 2) }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right">{{ $totalIngresos > 0 ? number_format(($totalNeto / $totalIngresos) * 100, 1) . '%' : 'N/A' }}</td>
                                                    <td class="px-4 sm:px-6 py-4 text-right">{{ $totalRepases }}</td>
                                                    <td class="px-4 sm:px-6 py-4"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
