<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-cyan-600 text-xl fill">analytics</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Reportes Avanzados
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Panel de control para análisis financiero y productividad</p>
                </div>
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
                <nav class="flex mb-8 mx-4 sm:mx-0 text-sm text-gray-500" aria-label="Breadcrumb">
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Reportes</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {{-- Título de Sección --}}
                <div class="mb-8 mx-4 sm:mx-0">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Selecciona un Tipo de Reporte</h3>
                    <p class="text-gray-600">Elige el análisis que deseas visualizar</p>
                </div>

                {{-- Grid de Cards de Reportes --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mx-4 sm:mx-0">
                    
                    {{-- Card: Rentabilidad por Clínica --}}
                    <a href="{{ route('reportes.rentabilidad-clinica') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-cyan-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">business</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">
                                    Rentabilidad por Clínica
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Análisis financiero detallado por establecimiento médico. Visualiza ingresos, gastos, ganancia neta y margen de rentabilidad de cada clínica.
                                </p>
                                <div class="mt-4 flex items-center text-blue-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Rentabilidad por Examen --}}
                    <a href="{{ route('reportes.rentabilidad-examen') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">monitor_heart</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-green-600 transition-colors duration-200">
                                    Rentabilidad por Examen
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Análisis financiero por tipo de procedimiento médico. Identifica qué exámenes generan más ingresos y su rendimiento promedio.
                                </p>
                                <div class="mt-4 flex items-center text-green-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Productividad --}}
                    <a href="{{ route('reportes.productividad') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-purple-500 to-pink-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">bar_chart</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors duration-200">
                                    Productividad
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Análisis de cantidad de exámenes realizados por período. Métricas de productividad diaria, por repase y desglose por tipo y clínica.
                                </p>
                                <div class="mt-4 flex items-center text-purple-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Comparativo --}}
                    <a href="{{ route('reportes.comparativo') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-orange-500 to-red-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">compare_arrows</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors duration-200">
                                    Comparativo
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Comparación de métricas entre diferentes períodos temporales. Identifica tendencias, crecimiento y variaciones porcentuales en el negocio.
                                </p>
                                <div class="mt-4 flex items-center text-orange-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Comparación de Clínicas --}}
                    <a href="{{ route('reportes.comparacion-clinicas') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">compare</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors duration-200">
                                    Comparación de Clínicas
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Compara el rendimiento financiero entre dos clínicas en el mismo período. Visualiza diferencias en ingresos, gastos y rentabilidad.
                                </p>
                                <div class="mt-4 flex items-center text-indigo-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Análisis de Consultas --}}
                    <a href="{{ route('reportes.analisis-consultas') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-teal-500 to-cyan-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">chat</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-teal-600 transition-colors duration-200">
                                    Análisis de Consultas
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Análisis detallado de consultas médicas por clínica. Visualiza cantidad de consultas, promedios, evolución mensual y ratio con exámenes.
                                </p>
                                <div class="mt-4 flex items-center text-teal-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Card: Detalle de Repases --}}
                    <a href="{{ route('repases.index') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-cyan-500 to-blue-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">receipt_long</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-cyan-600 transition-colors duration-200">
                                    Detalle de Repases
                                </h4>
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Listado de repases con desglose detallado de gastos. Filtra por fechas y clínica, explora cada repase y exporta a Excel.
                                </p>
                                <div class="mt-4 flex items-center text-cyan-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver reporte
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                </div>

                <!--
                {{-- Información Adicional --}}
                <div class="mt-12 mx-4 sm:mx-0">
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-2xl p-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h5 class="text-lg font-bold text-gray-900 mb-2">Acerca de los Reportes Avanzados</h5>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    Los reportes avanzados te permiten analizar en profundidad el desempeño financiero y operativo de tu negocio médico. 
                                    Cada reporte incluye filtros personalizables, visualizaciones gráficas y opciones de exportación a Excel y PDF.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                -->

            </div>
        </div>
    </div>
</x-app-layout>
