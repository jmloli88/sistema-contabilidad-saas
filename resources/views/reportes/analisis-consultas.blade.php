<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Análisis de Consultas Médicas
                </h2>
                <p class="text-sm text-gray-600 mt-1">Análisis detallado de consultas médicas por clínica y período</p>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Análisis de Consultas</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Filtros --}}
                @include('reportes.partials.filtros', [
                    'route' => route('reportes.analisis-consultas'),
                    'filtros' => $filtros,
                    'clinicas' => $clinicas,
                    'showExamenFilter' => false
                ])

                {{-- Mensaje de datos vacíos --}}
                @if($datos['total_consultas'] == 0)
                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-6 mx-4 sm:mx-0 mb-8">
                        <div class="flex items-center space-x-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="text-lg font-bold text-yellow-900">No se encontraron datos</h4>
                                <p class="text-sm text-yellow-700">No hay datos disponibles para los filtros seleccionados. Intenta ajustar el rango de fechas o la clínica.</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Métricas Principales --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-8 mx-4 sm:mx-0">
                        {{-- Total Consultas --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Consultas</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['total_consultas'], 0) }}</p>
                            </div>
                        </div>

                        {{-- Consultas por Repase --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Consultas por Repase</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['consultas_por_repase'], 2) }}</p>
                            </div>
                        </div>

                        {{-- Ratio Exámenes/Consultas --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Ratio Exámenes/Consultas</h3>
                                <p class="text-3xl font-bold text-gray-900">
                                    @if($datos['ratio_examenes_consultas'])
                                        {{ number_format($datos['ratio_examenes_consultas'], 2) }}
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Desglose por Clínica --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Ranking por Clínica</h3>
                                </div>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:-mx-6 md:-mx-8">
                                <div class="inline-block min-w-full align-middle px-4 sm:px-6 md:px-8">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <tr>
                                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    #
                                                </th>
                                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Clínica
                                                </th>
                                                <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Total Consultas
                                                </th>
                                                <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Cantidad Repases
                                                </th>
                                                <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Consultas por Repase
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($datos['por_clinica'] as $index => $clinica)
                                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            @if($index == 0)
                                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 text-white font-bold text-sm">
                                                                    {{ $index + 1 }}
                                                                </span>
                                                            @elseif($index == 1)
                                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 text-white font-bold text-sm">
                                                                    {{ $index + 1 }}
                                                                </span>
                                                            @elseif($index == 2)
                                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white font-bold text-sm">
                                                                    {{ $index + 1 }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-700 font-semibold text-sm">
                                                                    {{ $index + 1 }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-semibold text-gray-900">{{ $clinica->nombre_clinica }}</div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                                        <div class="text-sm font-bold text-teal-600">{{ number_format($clinica->total_consultas, 0) }}</div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                                        <div class="text-sm text-gray-700">{{ number_format($clinica->cantidad_repases, 0) }}</div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                                        <div class="text-sm font-semibold text-gray-900">{{ number_format($clinica->consultas_por_repase, 2) }}</div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Gráfico de Evolución Mensual --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900">Evolución Mensual de Consultas</h3>
                            </div>
                            <div class="h-80">
                                <canvas id="evolucionConsultasChart"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Botones de Exportación --}}
                    <div class="flex flex-col sm:flex-row gap-4 mx-4 sm:mx-0">
                        <form action="{{ route('reportes.export.excel') }}" method="POST" class="flex-1">
                            @csrf
                            <input type="hidden" name="tipo" value="analisis-consultas">
                            <input type="hidden" name="fecha_inicio" value="{{ $filtros['fecha_inicio'] }}">
                            <input type="hidden" name="fecha_fin" value="{{ $filtros['fecha_fin'] }}">
                            @if($filtros['clinica_id'])
                                <input type="hidden" name="clinica_id" value="{{ $filtros['clinica_id'] }}">
                            @endif
                            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Exportar a Excel</span>
                            </button>
                        </form>

                        <form action="{{ route('reportes.export.pdf') }}" method="POST" class="flex-1">
                            @csrf
                            <input type="hidden" name="tipo" value="analisis-consultas">
                            <input type="hidden" name="fecha_inicio" value="{{ $filtros['fecha_inicio'] }}">
                            <input type="hidden" name="fecha_fin" value="{{ $filtros['fecha_fin'] }}">
                            @if($filtros['clinica_id'])
                                <input type="hidden" name="clinica_id" value="{{ $filtros['clinica_id'] }}">
                            @endif
                            <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Exportar a PDF</span>
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($datos['total_consultas'] > 0)
                // Gráfico de Evolución Mensual
                const evolucionCtx = document.getElementById('evolucionConsultasChart');
                if (evolucionCtx) {
                    new Chart(evolucionCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($datos['por_mes']->pluck('mes')) !!},
                            datasets: [{
                                label: 'Consultas',
                                data: {!! json_encode($datos['por_mes']->pluck('total_consultas')) !!},
                                backgroundColor: 'rgba(20, 184, 166, 0.2)',
                                borderColor: 'rgba(20, 184, 166, 1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                pointBackgroundColor: 'rgba(20, 184, 166, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }]
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
                                        padding: 15
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
                                            return 'Consultas: ' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return value.toLocaleString();
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 12
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            @endif
        });
    </script>
    @endpush
</x-app-layout>
