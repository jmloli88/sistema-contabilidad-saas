<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Forecasting de Gastos
                </h2>
                <p class="text-sm text-gray-600 mt-1">Predicciones con alertas automáticas y análisis por categoría</p>
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

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-red-50 to-pink-50">
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

                <!-- Alertas Activas -->
                @if(!isset($error) && isset($alerts) && count($alerts) > 0)
                    <div class="bg-orange-50 border border-orange-200 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-orange-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Alertas de Gastos ({{ count($alerts) }})
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($alerts as $alert)
                                    <div class="flex items-center p-4 bg-white rounded-lg border border-orange-200">
                                        <div class="flex-shrink-0 mr-3">
                                            <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $alert['message'] }}</p>
                                            <p class="text-xs text-gray-600">Categoría: {{ ucfirst($alert['category'] ?? 'General') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Filtros y Configuración -->
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-rose-300 to-pink-400 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Configuración de Forecast</h3>
                            </div>
                            <div class="text-sm text-gray-600">
                                Umbral de alerta: <span class="font-semibold text-red-600">{{ $thresholdConfig ?? 25 }}%</span>
                            </div>
                        </div>
                        
                        <form method="GET" action="{{ route('predictivo.gastos') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">Clínica</label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 block w-full p-3">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ ($filters['clinica_id'] ?? '') == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Período de Predicción -->
                            <div class="space-y-2">
                                <label for="months" class="block text-sm font-semibold text-gray-700">Período</label>
                                <select name="months" id="months" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 block w-full p-3">
                                    <option value="3" {{ ($months ?? 12) == 3 ? 'selected' : '' }}>3 meses</option>
                                    <option value="6" {{ ($months ?? 12) == 6 ? 'selected' : '' }}>6 meses</option>
                                    <option value="12" {{ ($months ?? 12) == 12 ? 'selected' : '' }}>12 meses</option>
                                    <option value="24" {{ ($months ?? 12) == 24 ? 'selected' : '' }}>24 meses</option>
                                </select>
                            </div>

                            <!-- Fecha Desde -->
                            <div class="space-y-2">
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">Fecha Desde</label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 block w-full p-3">
                            </div>

                            <!-- Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 block w-full p-3">
                            </div>

                            <!-- Botón de actualizar -->
                            <div class="md:col-span-4 flex justify-end">
                                <button type="submit" class="bg-gradient-to-r from-rose-300 to-pink-400 hover:from-red-600 hover:to-pink-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                        </svg>
                                        Actualizar Forecast
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(!isset($error))
                    <!-- Métricas Principales -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 mx-4 sm:mx-0">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">3 Meses</h4>
                                <div class="bg-red-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-red-600 mb-2">
                                ${{ number_format($expenseForecast->projections['3_months']['total'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Gastos proyectados</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">6 Meses</h4>
                                <div class="bg-orange-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-orange-600 mb-2">
                                ${{ number_format($expenseForecast->projections['6_months']['total'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Gastos proyectados</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">12 Meses</h4>
                                <div class="bg-purple-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-purple-600 mb-2">
                                ${{ number_format($expenseForecast->projections['12_months']['total'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Gastos proyectados</p>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 mx-4 sm:mx-0">
                        <!-- Gráfico Principal de Forecast -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Forecast de Gastos por Período</h3>
                                <div class="h-80">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico por Categorías -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Gastos por Categoría</h3>
                                <div class="h-80">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Análisis Detallado -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mx-4 sm:mx-0">
                        <!-- Correlación con Ingresos -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Correlación con Ingresos</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Coeficiente de Pearson:</span>
                                    <span class="font-bold text-2xl {{ ($correlation ?? 0) > 0.7 ? 'text-green-600' : (($correlation ?? 0) > 0.3 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ number_format($correlation ?? 0, 3) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-red-500 to-green-500 h-2 rounded-full" style="width: {{ abs($correlation ?? 0) * 100 }}%"></div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    @if(($correlation ?? 0) > 0.7)
                                        <p class="text-green-700">Correlación fuerte positiva. Los gastos aumentan proporcionalmente con los ingresos.</p>
                                    @elseif(($correlation ?? 0) > 0.3)
                                        <p class="text-yellow-700">Correlación moderada. Existe cierta relación entre ingresos y gastos.</p>
                                    @elseif(($correlation ?? 0) > -0.3)
                                        <p class="text-gray-700">Correlación débil. Los gastos no están fuertemente relacionados con los ingresos.</p>
                                    @else
                                        <p class="text-red-700">Correlación negativa. Los gastos tienden a disminuir cuando aumentan los ingresos.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Desglose por Categorías -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Desglose por Categorías</h4>
                            <div class="space-y-3">
                                @if(isset($expenseForecast) && isset($expenseForecast->categoryBreakdown))
                                    @foreach($expenseForecast->categoryBreakdown as $category => $amount)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-3 h-3 rounded-full {{ $category === 'personal' ? 'bg-blue-500' : ($category === 'equipos' ? 'bg-green-500' : ($category === 'suministros' ? 'bg-yellow-500' : 'bg-purple-500')) }}"></div>
                                                <span class="font-medium text-gray-900">{{ ucfirst($category) }}</span>
                                            </div>
                                            <span class="font-bold text-gray-900">${{ number_format($amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-gray-500 py-4">
                                        <p>No hay datos de categorías disponibles</p>
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
                // Gráfico principal de forecast
                const expenseCtx = document.getElementById('expenseChart').getContext('2d');
                const expenseChartData = @json($chartData);
                
                new Chart(expenseCtx, {
                    type: 'line',
                    data: expenseChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Forecast de Gastos por Período'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
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

                // Gráfico por categorías (si hay datos)
                @if(isset($expenseForecast) && method_exists($expenseForecast, 'getByCategory'))
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                const categoryData = @json($expenseForecast->getByCategory());
                
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(categoryData).map(key => key.charAt(0).toUpperCase() + key.slice(1)),
                        datasets: [{
                            data: Object.values(categoryData),
                            backgroundColor: [
                                'rgb(59, 130, 246)',   // blue
                                'rgb(34, 197, 94)',    // green  
                                'rgb(251, 191, 36)',   // yellow
                                'rgb(168, 85, 247)',   // purple
                                'rgb(239, 68, 68)',    // red
                                'rgb(6, 182, 212)'     // cyan
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            title: {
                                display: true,
                                text: 'Distribución por Categoría'
                            }
                        }
                    }
                });
                @endif
            });
        </script>
        @endpush
    @endif
</x-app-layout>
