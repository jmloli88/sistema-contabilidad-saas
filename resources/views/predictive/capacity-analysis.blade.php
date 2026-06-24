<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Análisis de Capacidad
                </h2>
                <p class="text-sm text-gray-600 mt-1">Utilización operativa y proyección de saturación</p>
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

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50">
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

                <!-- Alerta de Capacidad -->
                @if(!isset($error) && isset($capacityAnalysis) && $capacityAnalysis->getCurrentUtilization() > ($alertThreshold ?? 85))
                    <div class="bg-orange-50 border border-orange-200 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                        <div class="p-6">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="text-lg font-bold text-orange-900">Alerta de Capacidad</h3>
                                    <p class="text-orange-700">La utilización actual ({{ number_format($capacityAnalysis->getCurrentUtilization(), 1) }}%) supera el umbral de alerta ({{ $alertThreshold }}%)</p>
                                </div>
                            </div>
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
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01.293.707V12a1 1 0 102 0V9a1 1 0 01.293-.707L13.586 6H12a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293A1 1 0 0112 9v3a3 3 0 11-6 0V9a1 1 0 01.293-.707L8.586 6H7a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Análisis de Capacidad</h3>
                            </div>
                            <div class="text-sm text-gray-600">
                                Umbral de alerta: <span class="font-semibold text-blue-600">{{ $alertThreshold ?? 85 }}%</span>
                            </div>
                        </div>
                        
                        <form method="GET" action="{{ route('predictivo.capacidad') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">Clínica</label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
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
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
                            </div>

                            <!-- Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
                            </div>

                            <!-- Botón de actualizar -->
                            <div class="md:col-span-3 flex justify-end">
                                <button type="submit" class="bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
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
                        <!-- Utilización Actual -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Utilización Actual</h4>
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold {{ ($capacityAnalysis->getCurrentUtilization() ?? 0) > ($alertThreshold ?? 85) ? 'text-red-600' : 'text-blue-600' }} mb-2">
                                {{ number_format($capacityAnalysis->getCurrentUtilization() ?? 0, 1) }}%
                            </div>
                            <p class="text-sm text-gray-600">De la capacidad total</p>
                        </div>

                        <!-- Fecha de Saturación -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Saturación</h4>
                                <div class="bg-orange-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            @if($saturationDate)
                                <div class="text-2xl font-bold text-orange-600 mb-2">
                                    {{ $saturationDate->format('M Y') }}
                                </div>
                                <p class="text-sm text-gray-600">{{ $saturationDate->diffForHumans() }}</p>
                            @else
                                <div class="text-2xl font-bold text-green-600 mb-2">
                                    N/A
                                </div>
                                <p class="text-sm text-gray-600">Sin proyección de saturación</p>
                            @endif
                        </div>

                        <!-- Tasa de Crecimiento -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Crecimiento</h4>
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-green-600 mb-2">
                                {{ number_format($capacityAnalysis->getGrowthRate() ?? 0, 1) }}%
                            </div>
                            <p class="text-sm text-gray-600">Mensual promedio</p>
                        </div>

                        <!-- Cuellos de Botella -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">Cuellos de Botella</h4>
                                <div class="bg-red-100 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-red-600 mb-2">
                                {{ count($capacityAnalysis->getBottlenecks() ?? []) }}
                            </div>
                            <p class="text-sm text-gray-600">Detectados</p>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 mx-4 sm:mx-0">
                        <!-- Gráfico de Utilización -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Utilización de Capacidad</h3>
                                <div class="h-80">
                                    <canvas id="capacityChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico por Clínicas -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">Utilización por Clínica</h3>
                                <div class="h-80">
                                    <canvas id="clinicChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recomendaciones y Cuellos de Botella -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mx-4 sm:mx-0">
                        <!-- Recomendaciones -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Recomendaciones
                            </h4>
                            <div class="space-y-3">
                                @if(isset($recommendations) && count($recommendations) > 0)
                                    @foreach($recommendations as $recommendation)
                                        <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                                            <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $recommendation['title'] ?? 'Recomendación' }}</p>
                                                <p class="text-sm text-gray-600">{{ $recommendation['description'] ?? $recommendation }}</p>
                                                @if(isset($recommendation['priority']))
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1 {{ $recommendation['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($recommendation['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                                        {{ ucfirst($recommendation['priority']) }} Priority
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-gray-500 py-4">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <p>No hay recomendaciones específicas en este momento</p>
                                        <p class="text-xs">La capacidad actual está dentro de parámetros normales</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Cuellos de Botella -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6">
                            <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Cuellos de Botella Detectados
                            </h4>
                            <div class="space-y-3">
                                @if(isset($capacityAnalysis) && count($capacityAnalysis->getBottlenecks()) > 0)
                                    @foreach($capacityAnalysis->getBottlenecks() as $bottleneck)
                                        <div class="flex items-start space-x-3 p-3 bg-red-50 rounded-lg">
                                            <div class="flex-shrink-0 w-2 h-2 bg-red-500 rounded-full mt-2"></div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $bottleneck['location'] ?? 'Área no especificada' }}</p>
                                                <p class="text-sm text-gray-600">{{ $bottleneck['description'] ?? $bottleneck }}</p>
                                                @if(isset($bottleneck['utilization']))
                                                    <p class="text-xs text-red-600 mt-1">Utilización: {{ number_format($bottleneck['utilization'], 1) }}%</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-gray-500 py-4">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <p>No se detectaron cuellos de botella</p>
                                        <p class="text-xs">El flujo operativo está funcionando correctamente</p>
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
                // Gráfico de utilización de capacidad
                const capacityCtx = document.getElementById('capacityChart').getContext('2d');
                const capacityChartData = @json($chartData);
                
                new Chart(capacityCtx, {
                    type: 'doughnut',
                    data: capacityChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            title: {
                                display: true,
                                text: 'Utilización vs Capacidad Disponible'
                            }
                        }
                    }
                });

                // Gráfico por clínicas (si hay datos)
                @if(isset($capacityAnalysis) && method_exists($capacityAnalysis, 'getUtilizationByClinic'))
                const clinicCtx = document.getElementById('clinicChart').getContext('2d');
                const clinicData = @json($capacityAnalysis->getUtilizationByClinic());
                
                new Chart(clinicCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(clinicData),
                        datasets: [{
                            label: 'Utilización (%)',
                            data: Object.values(clinicData),
                            backgroundColor: Object.values(clinicData).map(value => 
                                value > {{ $alertThreshold ?? 85 }} ? 'rgba(239, 68, 68, 0.8)' : 
                                value > 70 ? 'rgba(251, 191, 36, 0.8)' : 
                                'rgba(34, 197, 94, 0.8)'
                            ),
                            borderColor: Object.values(clinicData).map(value => 
                                value > {{ $alertThreshold ?? 85 }} ? 'rgb(239, 68, 68)' : 
                                value > 70 ? 'rgb(251, 191, 36)' : 
                                'rgb(34, 197, 94)'
                            ),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Utilización por Clínica'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
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
