<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-cyan-600 text-xl fill">insights</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Dashboard Predictivo
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Análisis predictivo y proyecciones financieras</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ $lastUpdate ?? now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </x-slot>

    <!-- Fondo con degradado sutil -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
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
                                <div class="bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Filtros Predictivos</h3>
                            </div>
                        </div>
                        
                        <form method="GET" action="{{ route('predictivo.dashboard') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Clínica
                                    </span>
                                </label>
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
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Desde
                                    </span>
                                </label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                            </div>

                            <!-- Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Hasta
                                    </span>
                                </label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                            </div>

                            <!-- Botón de filtrar -->
                            <div class="md:col-span-3 flex justify-end">
                                <button type="submit" class="bg-gradient-to-r from-purple-500 to-blue-600 hover:from-purple-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                        </svg>
                                        Aplicar Filtros
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(!isset($error))
                    <!-- Navigation Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mx-4 sm:mx-0">
                        <!-- Income Projection Card -->
                        <a href="{{ route('predictivo.ingresos') }}" class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Predicción de Ingresos</h3>
                                <p class="text-sm text-gray-600 mb-4">Proyecciones usando múltiples algoritmos</p>
                                @if(isset($dashboardData['income_summary']) && !isset($dashboardData['income_summary']['error']))
                                    <div class="text-2xl font-bold text-green-600">
                                        ${{ number_format($dashboardData['income_summary']['next_3_months'] ?? 0, 0, ',', '.') }}
                                    </div>
                                    <p class="text-xs text-gray-500">Próximos 3 meses</p>
                                @else
                                    <p class="text-sm text-orange-600">{{ $dashboardData['income_summary']['error'] ?? 'Datos insuficientes' }}</p>
                                @endif
                            </div>
                        </a>

                        <!-- Expense Forecast Card -->
                        <a href="{{ route('predictivo.gastos') }}" class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Forecasting de Gastos</h3>
                                <p class="text-sm text-gray-600 mb-4">Predicciones con alertas automáticas</p>
                                @if(isset($dashboardData['expense_summary']) && !isset($dashboardData['expense_summary']['error']))
                                    <div class="text-2xl font-bold text-red-600">
                                        ${{ number_format($dashboardData['expense_summary']['next_3_months'] ?? 0, 0, ',', '.') }}
                                    </div>
                                    <p class="text-xs text-gray-500">Próximos 3 meses</p>
                                    @if(($dashboardData['expense_summary']['alerts_count'] ?? 0) > 0)
                                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ $dashboardData['expense_summary']['alerts_count'] }} alertas
                                        </div>
                                    @endif
                                @else
                                    <p class="text-sm text-orange-600">{{ $dashboardData['expense_summary']['error'] ?? 'Datos insuficientes' }}</p>
                                @endif
                            </div>
                        </a>

                        <!-- Capacity Analysis Card -->
                        <a href="{{ route('predictivo.capacidad') }}" class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01.293.707V12a1 1 0 102 0V9a1 1 0 01.293-.707L13.586 6H12a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293A1 1 0 0112 9v3a3 3 0 11-6 0V9a1 1 0 01.293-.707L8.586 6H7a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Análisis de Capacidad</h3>
                                <p class="text-sm text-gray-600 mb-4">Utilización y proyección de saturación</p>
                                @if(isset($dashboardData['capacity_summary']) && !isset($dashboardData['capacity_summary']['error']))
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ number_format($dashboardData['capacity_summary']['current_utilization'] ?? 0, 1) }}%
                                    </div>
                                    <p class="text-xs text-gray-500">Utilización actual</p>
                                @else
                                    <p class="text-sm text-orange-600">{{ $dashboardData['capacity_summary']['error'] ?? 'Datos insuficientes' }}</p>
                                @endif
                            </div>
                        </a>

                        <!-- Trend Analysis Card -->
                        <a href="{{ route('predictivo.tendencias') }}" class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Análisis de Tendencias</h3>
                                <p class="text-sm text-gray-600 mb-4">Patrones estacionales y comparaciones</p>
                                @if(isset($dashboardData['trend_summary']) && !isset($dashboardData['trend_summary']['error']))
                                    <div class="text-2xl font-bold text-purple-600">
                                        {{ ucfirst($dashboardData['trend_summary']['trend_direction'] ?? 'Estable') }}
                                    </div>
                                    <p class="text-xs text-gray-500">Dirección de tendencia</p>
                                @else
                                    <p class="text-sm text-orange-600">{{ $dashboardData['trend_summary']['error'] ?? 'Datos insuficientes' }}</p>
                                @endif
                            </div>
                        </a>
                    </div>

                    <!-- System Health Status -->
                    @if(isset($systemHealth))
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Estado del Sistema</h3>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold {{ $systemHealth['status'] === 'healthy' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $systemHealth['status'] === 'healthy' ? '✓' : '✗' }}
                                        </div>
                                        <p class="text-sm text-gray-600">Estado General</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ $systemHealth['cache_hit_rate'] }}%</div>
                                        <p class="text-sm text-gray-600">Tasa de Caché</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-purple-600">{{ $systemHealth['prediction_accuracy'] }}%</div>
                                        <p class="text-sm text-gray-600">Precisión Promedio</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-700">{{ $systemHealth['last_job_run']->diffForHumans() }}</div>
                                        <p class="text-sm text-gray-600">Última Actualización</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Active Alerts -->
                    @if(isset($dashboardData['alerts']) && count($dashboardData['alerts']) > 0)
                        <div class="bg-orange-50 border border-orange-200 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-orange-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Alertas Activas
                                </h3>
                                <div class="space-y-3">
                                    @foreach($dashboardData['alerts'] as $alert)
                                        <div class="flex items-center p-3 bg-white rounded-lg border border-orange-200">
                                            <div class="flex-shrink-0 mr-3">
                                                @if($alert['level'] === 'warning')
                                                    <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($alert['level'] === 'error')
                                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($alert['level'] === 'info')
                                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $alert['message'] }}</p>
                                                <p class="text-xs text-gray-600">Tipo: {{ ucfirst($alert['type']) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>