<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Precios de Exámenes</h2>
    </x-slot>

    <div x-data="{ openCreate: {{ $errors->any() ? 'true' : 'false' }} }" class="py-12 min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Nuevo Examen Button -->
            <div class="mb-4 flex justify-end">
                <button @click="openCreate = true" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 min-h-[44px] touch-manipulation">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Examen
                </button>
            </div>

            @if($examenes->isEmpty())
                <div class="bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg shadow-xl">
                    <x-empty-state
                        icon="checklist"
                        title="No hay exámenes"
                        description="Crea un nuevo examen para comenzar."
                        action="{{ route('examenes.index') }}"
                        actionLabel="Nuevo Examen"
                    />
                </div>
            @else
            <!-- Vista de Tabla (Desktop) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg shadow-xl hover:shadow-2xl transition-shadow duration-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Examen
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">
                                    <div class="flex items-center justify-end gap-2">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                        </svg>
                                        Precio Sin Nota
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">
                                    <div class="flex items-center justify-end gap-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                        Precio Con Nota
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-center font-bold">Estado</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examenes as $examen)
                                <tr class="bg-white/50 border-b border-gray-200 hover:bg-indigo-50/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $examen->nombre }}
                                        @if(($examen->overrides_count ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800">
                                                {{ $examen->overrides_count }} {{ $examen->overrides_count === 1 ? 'clínica' : 'clínicas' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                        R${{ number_format($examen->precio_sin_nota, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-blue-900">
                                        R${{ number_format($examen->precio_con_nota, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($examen->is_active)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-red-100 text-red-800">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 justify-start">
                                            <a href="{{ route('examenes.edit', $examen) }}" 
                                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation"
                                               title="Editar">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                </svg>
                                            </a>

                                            <form action="{{ route('examenes.toggle', $examen) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation
                                                    {{ $examen->is_active ? 'text-amber-600' : 'text-green-600' }}"
                                                   title="{{ $examen->is_active ? 'Desactivar' : 'Activar' }}">
                                                    @if($examen->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @endif
                                                </button>
                                            </form>

                                            @if($examen->repase_count === 0)
                                                <form action="{{ route('examenes.destroy', $examen) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('¿Está seguro de eliminar este examen? Esta acción no se puede deshacer.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation text-red-600"
                                                       title="Eliminar">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" disabled
                                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation text-gray-400 cursor-not-allowed"
                                                   title="No se puede eliminar: tiene repases asociados">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
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
                @foreach($examenes as $examen)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-4">
                            <!-- Header de la tarjeta -->
                            <div class="mb-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-semibold text-gray-900">
                                        {{ $examen->nombre }}
                                        @if(($examen->overrides_count ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800">
                                                {{ $examen->overrides_count }} {{ $examen->overrides_count === 1 ? 'clínica' : 'clínicas' }}
                                            </span>
                                        @endif
                                    </h3>
                                    @if($examen->is_active)
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-red-100 text-red-800">
                                            Inactivo
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Detalles de precios -->
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="text-xs text-gray-500 block">Precio Sin Nota</span>
                                        <span class="text-lg font-bold text-gray-900">R${{ number_format($examen->precio_sin_nota, 2) }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                    <div>
                                        <span class="text-xs text-blue-600 block">Precio Con Nota</span>
                                        <span class="text-lg font-bold text-blue-900">R${{ number_format($examen->precio_con_nota, 2) }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="pt-3 border-t border-gray-200 flex flex-col gap-2">
                                <a href="{{ route('examenes.edit', $examen) }}" class="block text-center text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg>
                                    Editar Precios
                                </a>
                                <div class="flex gap-2">
                                    <form action="{{ route('examenes.toggle', $examen) }}" method="POST" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                           class="w-full text-center py-2 font-medium rounded-lg text-sm transition-all duration-200
                                           {{ $examen->is_active ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-green-700 bg-green-50 hover:bg-green-100' }}">
                                            {{ $examen->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    @if($examen->repase_count === 0)
                                        <form action="{{ route('examenes.destroy', $examen) }}" method="POST" class="flex-1"
                                              onsubmit="return confirm('¿Está seguro de eliminar este examen? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                               class="w-full text-center py-2 font-medium rounded-lg text-sm text-red-700 bg-red-50 hover:bg-red-100 transition-all duration-200">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Create Modal --}}
        <div x-show="openCreate" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;" x-cloak>
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="openCreate = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Nuevo Examen</h3>
                    <button @click="openCreate = false" class="p-1 rounded hover:bg-gray-100 text-gray-400">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form action="{{ route('examenes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre del Examen</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Precio Sin Nota</label>
                            <input type="number" step="0.01" min="0" name="precio_sin_nota" value="{{ old('precio_sin_nota') }}" required
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('precio_sin_nota')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Precio Con Nota</label>
                            <input type="number" step="0.01" min="0" name="precio_con_nota" value="{{ old('precio_con_nota') }}" required
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('precio_con_nota')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">El precio con nota debe ser mayor que el precio sin nota.</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openCreate = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 touch-manipulation min-h-[44px]">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 touch-manipulation min-h-[44px]">Guardar Examen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
