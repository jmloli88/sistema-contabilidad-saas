{{--
    Componente reutilizable de filtros para reportes
    
    Parámetros:
    - $route: Ruta a la que se enviará el formulario
    - $filtros: Array con valores actuales de filtros (fecha_inicio, fecha_fin, clinica_id, examen_id)
    - $clinicas: Colección de clínicas para el dropdown
    - $examenes: Colección de exámenes para el dropdown (opcional)
    - $showExamenFilter: Boolean para mostrar/ocultar filtro de examen (default: false)
--}}

<div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
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
        
        <form method="GET" action="{{ $route }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{{ $showExamenFilter ?? false ? '4' : '3' }} gap-4 sm:gap-6">
            {{-- Fecha Inicio --}}
            <div class="space-y-2">
                <label for="fecha_inicio" class="block text-sm font-semibold text-gray-700">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                        Fecha Inicio
                    </span>
                </label>
                <input 
                    type="date" 
                    name="fecha_inicio" 
                    id="fecha_inicio" 
                    value="{{ $filtros['fecha_inicio'] ?? '' }}" 
                    required
                    class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                >
            </div>

            {{-- Fecha Fin --}}
            <div class="space-y-2">
                <label for="fecha_fin" class="block text-sm font-semibold text-gray-700">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                        Fecha Fin
                    </span>
                </label>
                <input 
                    type="date" 
                    name="fecha_fin" 
                    id="fecha_fin" 
                    value="{{ $filtros['fecha_fin'] ?? '' }}" 
                    required
                    min="{{ $filtros['fecha_inicio'] ?? '' }}"
                    class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                >
            </div>

            {{-- Clínica --}}
            <div class="space-y-2">
                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                        </svg>
                        Clínica
                    </span>
                </label>
                <select 
                    name="clinica_id" 
                    id="clinica_id" 
                    class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                >
                    <option value="">Todas las clínicas</option>
                    @foreach($clinicas as $clinica)
                        <option value="{{ $clinica->id }}" {{ ($filtros['clinica_id'] ?? '') == $clinica->id ? 'selected' : '' }}>
                            {{ $clinica->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Examen (condicional) --}}
            @if($showExamenFilter ?? false)
                <div class="space-y-2">
                    <label for="examen_id" class="block text-sm font-semibold text-gray-700">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Examen
                        </span>
                    </label>
                    <select 
                        name="examen_id" 
                        id="examen_id" 
                        class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300"
                    >
                        <option value="">Todos los exámenes</option>
                        @foreach($examenes ?? [] as $examen)
                            <option value="{{ $examen->id }}" {{ ($filtros['examen_id'] ?? '') == $examen->id ? 'selected' : '' }}>
                                {{ $examen->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Botones --}}
            <div class="sm:col-span-2 md:col-span-{{ $showExamenFilter ?? false ? '4' : '3' }} flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="w-full sm:w-auto text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus:ring-4 focus:ring-cyan-200 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                    </svg>
                    Aplicar Filtros
                </button>
                <a href="{{ $route }}" class="w-full sm:w-auto text-center text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 hover:border-gray-400">
                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Limpiar Filtros
                </a>
            </div>
        </form>
    </div>
</div>
