<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Reporte de Productividad
                </h2>
                <p class="text-sm text-gray-600 mt-1">Análisis de cantidad de exámenes realizados por período</p>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Productividad</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Filtros --}}
                @include('reportes.partials.filtros', [
                    'route' => route('reportes.productividad'),
                    'filtros' => $filtros,
                    'clinicas' => $clinicas,
                    'showExamenFilter' => false
                ])

                {{-- Mensaje de datos vacíos --}}
                @if($datos['total_examenes_realizados'] == 0)
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 mx-4 sm:mx-0">
                        {{-- Total Exámenes Realizados --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Exámenes</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['total_examenes_realizados'], 0) }}</p>
                            </div>
                        </div>

                        {{-- Exámenes por Día --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Exámenes por Día</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['examenes_por_dia'], 2) }}</p>
                            </div>
                        </div>

                        {{-- Total Repases --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Repases</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['total_repases'], 0) }}</p>
                            </div>
                        </div>

                        {{-- Exámenes por Repase --}}
                        <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl transition-all duration-300 hover:shadow-2xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm4-4a1 1 0 100 2h.01a1 1 0 100-2H13zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zM7 8a1 1 0 000 2h.01a1 1 0 000-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-600 mb-1">Exámenes por Repase</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($datos['examenes_por_repase'], 2) }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Desglose por Tipo de Examen --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Desglose por Tipo de Examen</h3>
                                </div>
                                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ $datos['por_examen']->count() }} tipos</span>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm text-left text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Tipo de Examen</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Cantidad Total</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($datos['por_examen'] as $item)
                                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                    {{ $item->nombre_examen }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-center font-bold text-blue-600 whitespace-nowrap">
                                                    {{ number_format($item->cantidad_total, 0) }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                    {{ number_format(($item->cantidad_total / $datos['total_examenes_realizados']) * 100, 2) }}%
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

                    {{-- Tabla de Desglose por Clínica --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Desglose por Clínica</h3>
                                </div>
                                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ $datos['por_clinica']->count() }} clínicas</span>
                            </div>

                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full text-sm text-left text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Clínica</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Cantidad Total</th>
                                            <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($datos['por_clinica'] as $item)
                                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900 whitespace-nowrap">
                                                    {{ $item->nombre_clinica }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-center font-bold text-green-600 whitespace-nowrap">
                                                    {{ number_format($item->cantidad_total, 0) }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-right font-medium text-gray-700 whitespace-nowrap">
                                                    {{ number_format(($item->cantidad_total / $datos['total_examenes_realizados']) * 100, 2) }}%
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

                    {{-- Gráfico de Barras Horizontales --}}
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                        <div class="p-4 sm:p-6 md:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-2.5">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Visualización de Productividad por Examen</h3>
                                </div>
                            </div>
                            
                            <div class="relative w-full" style="height: 300px; min-height: 300px;">
                                <canvas id="chartProductividad"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Botones de Exportación --}}
                    @include('reportes.partials.botones-exportacion', [
                        'tipo' => 'productividad',
                        'filtros' => $filtros
                    ])
                @endif

            </div>
        </div>
    </div>

    @if($datos['total_examenes_realizados'] > 0)
        {{-- Chart.js Script --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('chartProductividad');
                
                const labels = @json($datos['por_examen']->pluck('nombre_examen'));
                const cantidadData = @json($datos['por_examen']->pluck('cantidad_total'));
                
                // Generar colores dinámicos
                const backgroundColors = cantidadData.map((_, index) => {
                    const colors = [
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
                    return colors[index % colors.length];
                });
                
                const borderColors = cantidadData.map((_, index) => {
                    const colors = [
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
                    return colors[index % colors.length];
                });
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Cantidad de Exámenes',
                            data: cantidadData,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
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
                                    padding: 20
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
                                            label += ': ';
                                        }
                                        label += context.parsed.x.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
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
                                        return value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                    }
                                }
                            },
                            y: {
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
