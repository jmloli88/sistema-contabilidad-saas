<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Análisis de Tendencias
                </h2>
                <p class="text-sm text-gray-600 mt-1">Patrones estacionales y comparaciones año-sobre-año</p>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('predictivo.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-violet-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                @if(isset($error))
                    <!-- Error Message -->
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8 mx-4 sm:mx-0">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-red-700 font-medium">{{ $error }}</p>
                        </div>
                    </div>
                @endif

                <!-- Filtros -->
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Análisis de Tendencias</h3>
                            </div>
                            <div class="text-sm text-gray-600">
                                Confianza: <span class="font-semibold text-purple-600">{{ $confidenceLevel ?? 95 }}%</span>
                            </div>
                        </div>
                        
                        <form method="GET" action="{{ route('predictivo.tendencias') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">Clínica</label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ ($filters['clinica_id'] ?? '') == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Fecha Desde -->
                            <div class="space-y-2">
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">Fecha Desde</label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                            </div>

                            <!-- Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                            </div>

                            <!-- Botón de actualizar -->
                            <div class="md:col-span-3 flex justify-end">
                                <button type="submit" class="bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-purple-600 hover:to-violet-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                        </svg>
                                        Actualizar Análisis
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(!isset($error))
                    <!-- Métricas Principales -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 mx-4 sm:mx-0">
                        <!-- Fuerza de Tendencia -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Fuerza de Tendencia</h4>
                                <div class="bg-purple-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-purple-600 mb-2">
                                {{ number_format($trendStrength ?? 0, 1) }}%
                            </div>
                            <p class="text-sm text-gray-600">Intensidad del patrón</p>
                        </div>

                        <!-- Estacionalidad -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Estacionalidad</h4>
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-blue-600 mb-2">
                                {{ number_format($seasonalPatterns->seasonalStrength ?? 0, 1) }}%
                            </div>
                            <p class="text-sm text-gray-600">Variación estacional</p>
                        </div>

                        <!-- Mes Pico -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Mes Pico</h4>
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-green-600 mb-2">
                                {{ $seasonalPatterns->metadata['peak_months'][0] ?? 'N/A' }}
                            </div>
                            <p class="text-sm text-gray-600">Mayor actividad</p>
                        </div>

                        <!-- Mes Bajo -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Mes Bajo</h4>
                                <div class="bg-red-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.414V7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-red-600 mb-2">
                                {{ $seasonalPatterns->metadata['low_months'][0] ?? 'N/A' }}
                            </div>
                            <p class="text-sm text-gray-600">Menor actividad</p>
                        </div>
                    </div>

                    <!-- Gráficos Principales -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 mx-4 sm:mx-0">
                        <!-- Gráfico de Patrones Estacionales -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Patrones Estacionales</h3>
                                <div class="h-80">
                                    <canvas id="seasonalChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Comparación Año-sobre-Año -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Comparación Año-sobre-Año</h3>
                                <div class="h-80">
                                    <canvas id="comparisonChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Análisis Detallado -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mx-4 sm:mx-0">
                        <!-- Variaciones Mensuales -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Variaciones Mensuales</h4>
                            <div class="space-y-3">
                                @if(isset($seasonalPatterns) && method_exists($seasonalPatterns, 'getMonthlyVariations'))
                                    @php
                                        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                                        $variations = $seasonalPatterns->monthlyPatterns;
                                    @endphp
                                    @foreach($months as $index => $month)
                                        @php
                                            $variation = $variations[$index] ?? 0;
                                            $isPositive = $variation >= 0;
                                        @endphp
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <span class="font-medium text-gray-900 w-8">{{ $month }}</span>
                                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full {{ $isPositive ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ abs($variation) }}%"></div>
                                                </div>
                                            </div>
                                            <span class="font-bold {{ $isPositive ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $isPositive ? '+' : '' }}{{ number_format($variation, 1) }}%
                                            </span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-gray-500 py-4">
                                        <p>No hay datos de variaciones mensuales disponibles</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Insights y Recomendaciones -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" clip-rule="evenodd"></path>
                                </svg>
                                Insights Clave
                            </h4>
                            <div class="space-y-4">
                                @if(($trendStrength ?? 0) > 70)
                                    <div class="flex items-start space-x-3 p-3 bg-purple-50 rounded-lg">
                                        <div class="flex-shrink-0 w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Tendencia Fuerte Detectada</p>
                                            <p class="text-sm text-gray-600">Los patrones son muy consistentes y predecibles.</p>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($seasonalPatterns) && $seasonalPatterns->seasonalStrength > 30)
                                    <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                                        <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Estacionalidad Significativa</p>
                                            <p class="text-sm text-gray-600">Planifique recursos según los picos estacionales.</p>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($seasonalPatterns) && count($seasonalPatterns->metadata['peak_months'] ?? []) > 0)
                                    <div class="flex items-start space-x-3 p-3 bg-green-50 rounded-lg">
                                        <div class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Meses de Mayor Demanda</p>
                                            <p class="text-sm text-gray-600">{{ implode(', ', $seasonalPatterns->metadata['peak_months'] ?? []) }} son los meses de mayor actividad.</p>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($seasonalPatterns) && count($seasonalPatterns->metadata['low_months'] ?? []) > 0)
                                    <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                                        <div class="flex-shrink-0 w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Oportunidad de Mejora</p>
                                            <p class="text-sm text-gray-600">Considere estrategias para {{ implode(', ', $seasonalPatterns->metadata['low_months'] ?? []) }}.</p>
                                        </div>
                                    </div>
                                @endif

                                @if(($trendStrength ?? 0) < 30)
                                    <div class="flex items-start space-x-3 p-3 bg-orange-50 rounded-lg">
                                        <div class="flex-shrink-0 w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Patrones Irregulares</p>
                                            <p class="text-sm text-gray-600">Los datos muestran alta variabilidad. Considere factores externos.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!isset($error) && isset($chartData))
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Gráfico de patrones estacionales
                const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
                const seasonalChartData = @json($chartData['seasonal']);
                
                new Chart(seasonalCtx, {
                    type: 'line',
                    data: seasonalChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Variación Estacional por Mes'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 6,
                                hoverRadius: 8
                            }
                        }
                    }
                });

                // Gráfico de comparación año-sobre-año (si hay datos)
                @if(isset($chartData['comparison']) && !empty($chartData['comparison']))
                const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
                const comparisonData = @json($chartData['comparison']);
                
                new Chart(comparisonCtx, {
                    type: 'bar',
                    data: comparisonData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Comparación Año-sobre-Año'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                @else
                // Mostrar mensaje si no hay datos de comparación
                const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
                comparisonCtx.font = '16px Arial';
                comparisonCtx.fillStyle = '#6B7280';
                comparisonCtx.textAlign = 'center';
                comparisonCtx.fillText('Datos insuficientes para comparación', comparisonCtx.canvas.width/2, comparisonCtx.canvas.height/2);
                @endif
            });
        </script>
        @endpush
    @endif
</x-app-layout>
