<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Repases Médicos
                </h2>
                <p class="text-sm text-gray-600 mt-1">Gestiona los repases y pagos médicos</p>
            </div>
            @if(Auth::user()->isAdmin())
                <a href="{{ route('repases.create') }}" class="inline-flex items-center justify-center text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-semibold rounded-xl text-sm px-5 py-2.5 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl sm:transform sm:hover:-translate-y-0.5 whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Nuevo Repase
                </a>
            @endif
        </div>
    </x-slot>

    <!-- Fondo con degradado sutil -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros con diseño moderno -->
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
                        <form method="GET" action="{{ route('repases.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <!-- Filtro por Clínica -->
                            <div class="space-y-2">
                                <label for="clinica_id" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Clínica
                                    </span>
                                </label>
                                <select name="clinica_id" id="clinica_id" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                                    <option value="">Todas las clínicas</option>
                                    @foreach($clinicas as $clinica)
                                        <option value="{{ $clinica->id }}" {{ request('clinica_id') == $clinica->id ? 'selected' : '' }}>
                                            {{ $clinica->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Estado -->
                            <div class="space-y-2">
                                <label for="estado" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Estado
                                    </span>
                                </label>
                                <select name="estado" id="estado" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="pagado" {{ request('estado') == 'pagado' ? 'selected' : '' }}>Pagado</option>
                                </select>
                            </div>

                            <!-- Filtro por Fecha Desde -->
                            <div class="space-y-2">
                                <label for="fecha_desde" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Desde
                                    </span>
                                </label>
                                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                            </div>

                            <!-- Filtro por Fecha Hasta -->
                            <div class="space-y-2">
                                <label for="fecha_hasta" class="block text-sm font-semibold text-gray-700">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1.5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fecha Hasta
                                    </span>
                                </label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}" class="bg-white border-2 border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition-all duration-200 hover:border-gray-300">
                            </div>

                            <!-- Botones -->
                            <div class="md:col-span-2 lg:col-span-4 flex flex-wrap gap-3 pt-2">
                                <button type="submit" class="flex-1 sm:flex-none text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                    Aplicar Filtros
                                </button>
                                <a href="{{ route('repases.index') }}" class="flex-1 sm:flex-none text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 hover:border-gray-400">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                @if($repases->isEmpty())
                    <!-- Estado vacío -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl mx-4 sm:mx-0">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay repases</h3>
                            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo repase médico.</p>
                            @if(Auth::user()->isAdmin())
                                <div class="mt-6">
                                    <a href="{{ route('repases.create') }}" class="inline-flex items-center text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Nuevo Repase
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Vista de Tabla (Desktop) -->
                    <div class="hidden md:block bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl"
                         x-data="{ repasesAbiertos: {} }">
                        <div class="p-4 sm:p-6 border-b border-gray-100">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-500">{{ $repases->total() }} repases</span>
                                </div>
                                @if(Auth::user()->isAdmin())
                                <form id="export-form" action="{{ route('reportes.export.excel') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="tipo" value="detalle-repases">
                                    <input type="hidden" name="fecha_inicio" value="{{ request('fecha_desde', now()->subMonth()->startOfMonth()->format('Y-m-d')) }}">
                                    <input type="hidden" name="fecha_fin" value="{{ request('fecha_hasta', now()->format('Y-m-d')) }}">
                                    @if(request('clinica_id'))
                                        <input type="hidden" name="clinica_id" value="{{ request('clinica_id') }}">
                                    @endif
                                    <div id="selected-ids-container"></div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        Exportar Excel
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th scope="col" class="px-3 py-4 font-bold text-center">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        </th>
                                        <th scope="col" class="px-6 py-4 font-bold">Fecha</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Clínica</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Estado</th>
                                        <th scope="col" class="px-6 py-4 text-right font-bold">Ingresos</th>
                                        <th scope="col" class="px-6 py-4 text-right font-bold">Gastos</th>
                                        <th scope="col" class="px-6 py-4 text-right font-bold">Neto</th>
                                        <th scope="col" class="px-6 py-4 text-center font-bold">Gastos</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($repases as $repase)
                                        @php
                                            $rowId = 'repase-' . $repase->id;
                                        @endphp
                                        <tr class="bg-white/50 border-b border-gray-100 hover:bg-white/80 transition-all duration-200">
                                            <td class="px-3 py-4 text-center">
                                                <input type="checkbox" name="repase_ids[]" value="{{ $repase->id }}" class="repase-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    {{ $repase->fecha->format('d/m/Y') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-gray-900">{{ $repase->clinica->nombre }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($repase->estado === 'pendiente')
                                                    <span class="inline-flex items-center bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 rounded-full">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Pendiente
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Pagado
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right text-green-600 font-medium whitespace-nowrap">R$ {{ number_format($repase->total_examenes, 2) }}</td>
                                            <td class="px-6 py-4 text-right text-red-600 font-medium whitespace-nowrap">R$ {{ number_format($repase->total_gastos, 2) }}</td>
                                            <td class="px-6 py-4 text-right font-semibold whitespace-nowrap {{ $repase->total_neto >= 0 ? 'text-gray-900' : 'text-red-600' }}">R$ {{ number_format($repase->total_neto, 2) }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <button @click="repasesAbiertos['{{ $rowId }}'] = !repasesAbiertos['{{ $rowId }}']"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200"
                                                        :class="repasesAbiertos['{{ $rowId }}'] ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-700 hover:bg-teal-100 hover:text-teal-700'">
                                                    <span x-text="repasesAbiertos['{{ $rowId }}'] ? 'Ocultar' : 'Ver'"></span>
                                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200" :class="repasesAbiertos['{{ $rowId }}'] ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('repases.show', $repase) }}" 
                                                       class="inline-flex items-center justify-center w-9 h-9 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-all duration-200 hover:scale-110"
                                                       title="Ver detalles">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </a>
                                                    
                                                    @if(Auth::user()->isAdmin())
                                                        <a href="{{ route('repases.edit', $repase) }}" 
                                                           class="inline-flex items-center justify-center w-9 h-9 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-all duration-200 hover:scale-110"
                                                           title="Editar">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        @if($repase->estado === 'pendiente')
                                                            <form action="{{ route('repases.destroy', $repase) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" 
                                                                        class="inline-flex items-center justify-center w-9 h-9 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-all duration-200 hover:scale-110"
                                                                        title="Eliminar"
                                                                        onclick="return confirm('¿Está seguro de eliminar este repase?')">
                                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        <tr x-show="repasesAbiertos['{{ $rowId }}']"
                                            x-transition:enter="transition-all duration-300 ease-out"
                                            x-transition:enter-start="opacity-0 -translate-y-2"
                                            x-transition:enter-end="opacity-100 translate-y-0">
                                            <td colspan="9" class="px-0 py-0">
                                                <div class="bg-teal-50 border-t-2 border-teal-200 p-4 sm:p-6">
                                                    <div class="flex items-center mb-3">
                                                        <svg class="w-4 h-4 text-teal-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                                                        <span class="font-semibold text-sm text-teal-800">Desglose de Gastos</span>
                                                    </div>
                                                    @if($repase->gastos->isNotEmpty())
                                                        <div class="overflow-x-auto">
                                                            <table class="min-w-full text-xs">
                                                                <thead>
                                                                    <tr class="border-b border-teal-200">
                                                                        <th class="px-3 py-2 text-left font-semibold text-teal-700">Tipo</th>
                                                                        <th class="px-3 py-2 text-left font-semibold text-teal-700">Descripción</th>
                                                                        <th class="px-3 py-2 text-right font-semibold text-teal-700">Monto</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-teal-100">
                                                                    @foreach($repase->gastos as $gasto)
                                                                    <tr class="hover:bg-teal-100/50">
                                                                        <td class="px-3 py-1.5 text-gray-600 capitalize">{{ $gasto->tipo }}</td>
                                                                        <td class="px-3 py-1.5 text-gray-700">{{ $gasto->descripcion }}</td>
                                                                        <td class="px-3 py-1.5 text-right text-red-600 font-medium">R$ {{ number_format($gasto->monto, 2) }}</td>
                                                                    </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr class="border-t-2 border-teal-300 font-semibold">
                                                                        <td colspan="2" class="px-3 py-2 text-right text-teal-800">Total Gastos:</td>
                                                                        <td class="px-3 py-2 text-right text-red-700">R$ {{ number_format($repase->total_gastos, 2) }}</td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-teal-600 italic">No hay gastos registrados para este repase.</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Vista de Tarjetas (Mobile) -->
                    <div class="md:hidden space-y-4 px-4">
                        @foreach($repases as $repase)
                            <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                                <div class="p-4">
                                    <!-- Header de la tarjeta -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $repase->clinica->nombre }}</h3>
                                            <p class="text-sm text-gray-500">{{ $repase->fecha->format('d/m/Y') }}</p>
                                        </div>
                                        @if($repase->estado === 'pendiente')
                                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Pendiente</span>
                                        @else
                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Pagado</span>
                                        @endif
                                    </div>

                                    <!-- Detalles -->
                                    <div class="space-y-2 mb-4">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Ingresos:</span>
                                            <span class="font-medium text-green-600">R${{ number_format($repase->total_examenes + $repase->total_consultas, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Total Egresos:</span>
                                            <span class="font-medium text-red-600">R${{ number_format($repase->total_gastos, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                            <span class="text-gray-700 font-semibold">Total Neto:</span>
                                            <span class="font-bold text-lg text-gray-900">R${{ number_format($repase->total_neto, 2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex gap-2 pt-3 border-t border-gray-200">
                                        <a href="{{ route('repases.show', $repase) }}" class="flex-1 text-center text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                            Ver
                                        </a>
                                        @if(Auth::user()->isAdmin())
                                            <a href="{{ route('repases.edit', $repase) }}" class="flex-1 text-center text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                                Editar
                                            </a>
                                            @if($repase->estado === 'pendiente')
                                                <form action="{{ route('repases.destroy', $repase) }}" method="POST" class="flex-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none" onclick="return confirm('¿Está seguro de eliminar este repase?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6">
                        {{ $repases->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.repase-checkbox');
            const exportForm = document.getElementById('export-form');
            const container = document.getElementById('selected-ids-container');

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            exportForm.addEventListener('submit', function(e) {
                container.innerHTML = '';
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'repase_ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    }
                });
            });
        });
    </script>
</x-app-layout>
