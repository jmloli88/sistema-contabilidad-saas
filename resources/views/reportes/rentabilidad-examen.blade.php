<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Rentabilidad por Tipo de Examen
                </h2>
                <p class="text-sm text-gray-600 mt-1">Análisis financiero detallado por procedimiento médico</p>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Rentabilidad por Examen</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Filtros --}}
                @include('reportes.partials.filtros', [
                    'route' => route('reportes.rentabilidad-examen'),
                    'filtros' => $filtros,
                    'clinicas' => $clinicas,
                    'examenes' => $examenes,
                    'showExamenFilter' => true
                ])

                {{-- Mensaje de datos vacíos --}}
                @if($datos->isEmpty())
                    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-6 mx-4 sm:mx-0 mb-8">
                        <div class="flex items-center space-x-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="text-lg font-bold text-yellow-900">No se encontraron datos</h4>
                                <p class="text-sm text-yellow-700">No hay datos disponibles para los filtros seleccionados. Intenta ajustar el rango de fechas, la clínica o el tipo de examen.</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Tabla de Rentabilidad --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900">Resultados</h3>
                                </div>
                                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ $datos->count() }} exámenes</span>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm text-left text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Nombre Examen</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Cantidad Total</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Total Ingresos</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Ingreso Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($datos as $item)
                                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                    {{ $item->nombre_examen }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-center font-medium text-gray-700 whitespace-nowrap">
                                                    {{ number_format($item->cantidad_total, 0) }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-bold text-green-600 whitespace-nowrap">
                                                    ${{ number_format($item->total_ingresos, 2) }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-900 whitespace-nowrap">
                                                    @if($item->ingreso_promedio !== null)
                                                        ${{ number_format($item->ingreso_promedio, 2) }}
                                                    @else
                                                        <span class="text-gray-400">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Gráfico de Pie --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Distribución de Ingresos por Examen</h3>
                                </div>
                            </div>
                            
                            <div class="relative flex justify-center items-center w-full" style="height: 300px; min-height: 300px;">
                                <canvas id="chartRentabilidadExamen"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Botones de Exportación --}}
                    @include('reportes.partials.botones-exportacion', [
                        'tipo' => 'rentabilidad-examen',
                        'filtros' => $filtros
                    ])
                @endif

            </div>
        </div>
    </div>

    @if(!$datos->isEmpty())
        {{-- Chart.js Script --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('chartRentabilidadExamen');
                
                const labels = @json($datos->pluck('nombre_examen'));
                const ingresosData = @json($datos->pluck('total_ingresos'));
                
                // Generar colores dinámicos para cada examen
                const backgroundColors = [
                    'rgba(59, 130, 246, 0.7)',   // blue
                    'rgba(16, 185, 129, 0.7)',   // green
                    'rgba(245, 158, 11, 0.7)',   // amber
                    'rgba(239, 68, 68, 0.7)',    // red
                    'rgba(139, 92, 246, 0.7)',   // purple
                    'rgba(236, 72, 153, 0.7)',   // pink
                    'rgba(20, 184, 166, 0.7)',   // teal
                    'rgba(251, 146, 60, 0.7)',   // orange
                    'rgba(168, 85, 247, 0.7)',   // violet
                    'rgba(34, 197, 94, 0.7)',    // emerald
                ];
                
                const borderColors = [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(20, 184, 166, 1)',
                    'rgba(251, 146, 60, 1)',
                    'rgba(168, 85, 247, 1)',
                    'rgba(34, 197, 94, 1)',
                ];
                
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Ingresos ($)',
                            data: ingresosData,
                            backgroundColor: backgroundColors.slice(0, labels.length),
                            borderColor: borderColors.slice(0, labels.length),
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    },
                                    padding: 15,
                                    boxWidth: 15,
                                    boxHeight: 15,
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                const percentage = ((value / total) * 100).toFixed(1);
                                                return {
                                                    text: `${label} (${percentage}%)`,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    strokeStyle: data.datasets[0].borderColor[i],
                                                    lineWidth: 2,
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
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
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        label += '$' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        label += ` (${percentage}%)`;
                                        return label;
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
