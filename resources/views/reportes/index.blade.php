<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Reportes Avanzados
                </h2>
                <p class="text-sm text-gray-600 mt-1">Panel de control para análisis financiero y productividad</p>
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
                    <a href="{{ route('reportes.rentabilidad-clinica') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                    </svg>
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
                    <a href="{{ route('reportes.rentabilidad-examen') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                    </svg>
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
                    <a href="{{ route('reportes.productividad') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
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
                    <a href="{{ route('reportes.comparativo') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
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
                    <a href="{{ route('reportes.comparacion-clinicas') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                    </svg>
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
                    <a href="{{ route('reportes.analisis-consultas') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>
                                    </svg>
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
                    <a href="{{ route('repases.index') }}" class="group bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl p-4 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                    </svg>
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
