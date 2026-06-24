<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-cyan-600 text-xl fill">calendar_month</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Calendario de Pagos
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Visualiza los pagos programados</p>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Fondo con degradado sutil -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtro por Clínica con diseño moderno -->
                <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-md mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Filtros</h3>
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Personaliza tu vista</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label for="clinica_filter" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Clínica
                                    </span>
                                </label>
                                <select id="clinica_filter" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}">{{ $clinica->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenedor del Calendario con diseño moderno -->
                <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-2xl mx-4 sm:mx-0">
                    <div class="p-6">
                        <div id="calendar"></div>
                    </div>
                </div>

                <!-- Leyenda de Colores con diseño moderno -->
                <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-md mt-6 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Leyenda</h3>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-6">
                            <div class="flex items-center gap-2">
                                <span class="flex w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-900">Pendiente</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="flex w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-900">Pagado</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
