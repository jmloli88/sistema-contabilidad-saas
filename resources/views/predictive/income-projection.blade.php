<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Predicción de Ingresos
                </h2>
                <p class="text-sm text-gray-600 mt-1">Proyecciones usando múltiples algoritmos predictivos</p>
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

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-green-50 to-emerald-50">
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

                <!-- Filtros y Configuración -->
                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Configuración de Predicción</h3>
                            </div>
                        </div>
                        
                        <form method="GET" action="{{ route('predictivo.ingresos') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">Clínica</label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ ($filters['clinica_id'] ?? '') == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Algoritmo de Predicción -->
                            <div class="space-y-2">
                                <label for="algorithm" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Algoritmo
                                    </span>
                                </label>
                                <select name="algorithm" id="algorithm" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3" onchange="updateAlgorithmInfo()">
                                    <option value="linear_regression" {{ ($filters['algorithm'] ?? 'linear_regression') == 'linear_regression' ? 'selected' : '' }} data-description="Proyección basada en tendencia lineal">
                                        Regresión Lineal
                                    </option>
                                    <option value="moving_average" {{ ($filters['algorithm'] ?? '') == 'moving_average' ? 'selected' : '' }} data-description="Promedio de períodos anteriores">
                                        Promedio Móvil
                                    </option>
                                    <option value="seasonal" {{ ($filters['algorithm'] ?? '') == 'seasonal' ? 'selected' : '' }} data-description="Considera patrones estacionales">
                                        Estacional
                                    </option>
                                </select>
                                <div id="algorithmInfo" class="mt-2 text-xs text-gray-500 italic"></div>
                            </div>

                            <!-- Período de Predicción -->
                            <div class="space-y-2">
                                <label for="months" class="block text-sm font-semibold text-gray-700">Período</label>
                                <select name="months" id="months" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3">
                                    <option value="3" {{ ($months ?? 12) == 3 ? 'selected' : '' }}>3 meses</option>
                                    <option value="6" {{ ($months ?? 12) == 6 ? 'selected' : '' }}>6 meses</option>
                                    <option value="12" {{ ($months ?? 12) == 12 ? 'selected' : '' }}>12 meses</option>
                                    <option value="24" {{ ($months ?? 12) == 24 ? 'selected' : '' }}>24 meses</option>
                                </select>
                            </div>

                            <!-- Fecha Desde -->
                            <div class="space-y-2">
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">Fecha Desde</label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3">
                            </div>

                            <!-- Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full p-3">
                            </div>

                            <!-- Botón de actualizar -->
                            <div class="md:col-span-5 flex justify-end">
                                <button type="submit" class="bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                        </svg>
                                        Actualizar Predicción
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
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-green-600 mb-2" id="metric-3-months">
                                ${{ number_format($incomeProjections->getProjection('3_months') ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Proyección a corto plazo</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">6 Meses</h4>
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-blue-600 mb-2" id="metric-6-months">
                                ${{ number_format($incomeProjections->getProjection('6_months') ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Proyección a medio plazo</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">12 Meses</h4>
                                <div class="bg-purple-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-purple-600 mb-2" id="metric-12-months">
                                ${{ number_format($incomeProjections->getProjection('12_months') ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-sm text-gray-600">Proyección a largo plazo</p>
                        </div>
                    </div>

                    <!-- Gráfico Principal -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Proyección de Ingresos</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Algoritmo: 
                                        <span class="font-medium text-green-600" id="chart-algorithm-name">
                                            @switch($incomeProjections->algorithm ?? 'linear_regression')
                                                @case('linear_regression')
                                                    Regresión Lineal
                                                    @break
                                                @case('moving_average')
                                                    Promedio Móvil
                                                    @break
                                                @case('seasonal')
                                                    Estacional
                                                    @break
                                                @default
                                                    {{ ucfirst(str_replace('_', ' ', $incomeProjections->algorithm ?? 'Combinado')) }}
                                            @endswitch
                                        </span>
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">Confianza:</span>
                                    <span class="text-sm font-bold text-green-600" id="chart-confidence">{{ number_format($incomeProjections->accuracy ?? 85, 1) }}%</span>
                                </div>
                            </div>
                            <div class="h-96">
                                <canvas id="incomeChart"></canvas>
                                <div id="chartFallback" class="hidden h-full flex items-center justify-center text-gray-500">
                                    <div class="text-center">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <p class="text-sm">Cargando gráfico...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparación de Algoritmos -->
                    @if(isset($availableAlgorithms) && count($availableAlgorithms) > 0)
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Comparación de Algoritmos</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    @foreach($availableAlgorithms as $algorithm)
                                        <div class="algorithm-card border {{ ($incomeProjections->algorithm ?? 'linear_regression') == $algorithm ? 'border-green-500 bg-green-50' : 'border-gray-200' }} rounded-xl p-4 {{ ($incomeProjections->algorithm ?? 'linear_regression') == $algorithm ? 'ring-2 ring-green-200' : '' }} cursor-pointer transition-all duration-200 hover:shadow-lg hover:border-green-300" 
                                             onclick="switchAlgorithm('{{ $algorithm }}')" 
                                             data-algorithm="{{ $algorithm }}">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $algorithm)) }}</h4>
                                                    @if(($incomeProjections->algorithm ?? 'linear_regression') == $algorithm)
                                                        <span class="active-badge inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Activo
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="algorithm-accuracy text-sm font-medium text-green-600">{{ number_format($algorithmResults[$algorithm]['accuracy'] ?? 0, 1) }}%</span>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-600">3 meses:</span>
                                                    <span class="font-medium">${{ number_format($algorithmResults[$algorithm]['3_months'] ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-600">6 meses:</span>
                                                    <span class="font-medium">${{ number_format($algorithmResults[$algorithm]['6_months'] ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-600">12 meses:</span>
                                                    <span class="font-medium">${{ number_format($algorithmResults[$algorithm]['12_months'] ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Información Adicional -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mx-4 sm:mx-0">
                        <!-- Tendencia -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Análisis de Tendencia</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Dirección:</span>
                                    <span class="font-medium {{ ($incomeProjections->metadata['trend'] ?? 'stable') === 'up' ? 'text-green-600' : (($incomeProjections->metadata['trend'] ?? 'stable') === 'down' ? 'text-red-600' : 'text-gray-600') }}">
                                        {{ ucfirst($incomeProjections->metadata['trend'] ?? 'Estable') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Confianza:</span>
                                    <span class="font-medium text-blue-600">{{ number_format($incomeProjections->accuracy ?? 85, 1) }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Algoritmo Seleccionado:</span>
                                    <span class="font-medium text-purple-600" id="analysis-algorithm-name">
                                        @switch($incomeProjections->algorithm ?? 'linear_regression')
                                            @case('linear_regression')
                                                Regresión Lineal
                                                @break
                                            @case('moving_average')
                                                Promedio Móvil
                                                @break
                                            @case('seasonal')
                                                Estacional
                                                @break
                                            @default
                                                {{ ucfirst(str_replace('_', ' ', $incomeProjections->algorithm ?? 'Combinado')) }}
                                        @endswitch
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Recomendaciones -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Recomendaciones</h4>
                            <div class="space-y-3">
                                @if(($incomeProjections->metadata['trend'] ?? 'stable') === 'up')
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                        <p class="text-sm text-gray-700">Tendencia positiva detectada. Considere expandir servicios.</p>
                                    </div>
                                @elseif(($incomeProjections->metadata['trend'] ?? 'stable') === 'down')
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-2 h-2 bg-red-500 rounded-full mt-2"></div>
                                        <p class="text-sm text-gray-700">Tendencia descendente. Revise estrategias de marketing.</p>
                                    </div>
                                @else
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                        <p class="text-sm text-gray-700">Ingresos estables. Mantenga las operaciones actuales.</p>
                                    </div>
                                @endif
                                
                                @if(($incomeProjections->accuracy ?? 85) < 70)
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                                        <p class="text-sm text-gray-700">Confianza baja. Recopile más datos históricos.</p>
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
                try {
                    const ctx = document.getElementById('incomeChart').getContext('2d');
                    const chartData = @json($chartData);
                    
                    console.log('Chart data:', chartData); // Debug
                    
                    if (!chartData || !chartData.datasets || chartData.datasets.length === 0) {
                        throw new Error('No chart data available');
                    }
                    
                    currentChart = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Proyección de Ingresos por Período'
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
                    
                    // Ocultar fallback si el gráfico se carga correctamente
                    document.getElementById('chartFallback').classList.add('hidden');
                    
                } catch (error) {
                    console.error('Error loading chart:', error);
                    // Mostrar fallback si hay error
                    document.getElementById('incomeChart').style.display = 'none';
                    document.getElementById('chartFallback').classList.remove('hidden');
                    document.getElementById('chartFallback').innerHTML = `
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-red-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm text-red-600">Error al cargar el gráfico</p>
                            <p class="text-xs text-gray-500 mt-1">Los datos están disponibles en las métricas de arriba</p>
                        </div>
                    `;
                }
            });
            
            // Función para actualizar información del algoritmo
            function updateAlgorithmInfo() {
                const select = document.getElementById('algorithm');
                const info = document.getElementById('algorithmInfo');
                const selectedOption = select.options[select.selectedIndex];
                const description = selectedOption.getAttribute('data-description');
                
                if (description) {
                    info.textContent = description;
                } else {
                    info.textContent = '';
                }
            }
            
            // Variables globales para los datos de algoritmos
            const algorithmData = @json($algorithmResults);
            let currentChart = null;
            
            // Función para cambiar algoritmo dinámicamente
            function switchAlgorithm(algorithm) {
                // Mostrar indicador de carga
                showLoadingIndicator();
                
                // Simular un pequeño delay para la transición suave
                setTimeout(() => {
                    // Actualizar datos de las métricas principales
                    const data = algorithmData[algorithm];
                    if (data) {
                        document.getElementById('metric-3-months').textContent = '$' + new Intl.NumberFormat().format(Math.round(data['3_months']));
                        document.getElementById('metric-6-months').textContent = '$' + new Intl.NumberFormat().format(Math.round(data['6_months']));
                        document.getElementById('metric-12-months').textContent = '$' + new Intl.NumberFormat().format(Math.round(data['12_months']));
                        
                        // Actualizar confianza
                        document.getElementById('chart-confidence').textContent = data.accuracy.toFixed(1) + '%';
                        
                        // Actualizar nombres de algoritmo
                        const algorithmNames = {
                            'linear_regression': 'Regresión Lineal',
                            'moving_average': 'Promedio Móvil',
                            'seasonal': 'Estacional'
                        };
                        
                        const algorithmName = algorithmNames[algorithm] || algorithm;
                        document.getElementById('chart-algorithm-name').textContent = algorithmName;
                        document.getElementById('analysis-algorithm-name').textContent = algorithmName;
                        
                        // Actualizar gráfico
                        updateChart(data);
                        
                        // Actualizar tarjetas de comparación
                        updateAlgorithmCards(algorithm);
                        
                        // Actualizar selector en el formulario
                        document.getElementById('algorithm').value = algorithm;
                        updateAlgorithmInfo();
                        
                        // Ocultar indicador de carga
                        hideLoadingIndicator();
                    }
                }, 300);
            }
            
            // Función para mostrar indicador de carga
            function showLoadingIndicator() {
                document.querySelectorAll('.algorithm-card').forEach(card => {
                    card.style.opacity = '0.6';
                    card.style.pointerEvents = 'none';
                });
            }
            
            // Función para ocultar indicador de carga
            function hideLoadingIndicator() {
                document.querySelectorAll('.algorithm-card').forEach(card => {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                });
            }
            
            // Función para actualizar el gráfico
            function updateChart(data) {
                if (currentChart) {
                    const newData = [data['3_months'], data['6_months'], data['12_months']];
                    currentChart.data.datasets[0].data = newData;
                    currentChart.update('active');
                }
            }
            
            // Función para actualizar las tarjetas de algoritmos
            function updateAlgorithmCards(selectedAlgorithm) {
                document.querySelectorAll('.algorithm-card').forEach(card => {
                    const algorithm = card.getAttribute('data-algorithm');
                    const isActive = algorithm === selectedAlgorithm;
                    
                    // Actualizar estilos
                    if (isActive) {
                        card.className = 'algorithm-card border border-green-500 bg-green-50 rounded-xl p-4 ring-2 ring-green-200 cursor-pointer transition-all duration-200 hover:shadow-lg hover:border-green-300';
                    } else {
                        card.className = 'algorithm-card border border-gray-200 rounded-xl p-4 cursor-pointer transition-all duration-200 hover:shadow-lg hover:border-green-300';
                    }
                    
                    // Actualizar badge "Activo"
                    const badge = card.querySelector('.active-badge');
                    if (isActive && !badge) {
                        // Agregar badge si no existe
                        const titleDiv = card.querySelector('.flex.items-center.space-x-2');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'active-badge inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
                        newBadge.innerHTML = `
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Activo
                        `;
                        titleDiv.appendChild(newBadge);
                    } else if (!isActive && badge) {
                        // Remover badge si existe
                        badge.remove();
                    }
                });
            }
            
            // Inicializar información del algoritmo
            document.addEventListener('DOMContentLoaded', function() {
                updateAlgorithmInfo();
            });
        </script>
        @endpush
    @endif
</x-app-layout>
