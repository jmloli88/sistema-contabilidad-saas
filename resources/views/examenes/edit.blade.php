<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Precios: {{ $examen->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('examenes.update', $examen) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <x-input-label for="nombre" value="Nombre del Examen *" />
                            <x-text-input id="nombre" type="text" name="nombre" 
                                          :value="old('nombre', $examen->nombre)" required class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="precio_sin_nota" value="Precio Sin Nota *" />
                            <x-text-input id="precio_sin_nota" type="number" step="0.01" min="0" name="precio_sin_nota" 
                                          :value="old('precio_sin_nota', $examen->precio_sin_nota)" required class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('precio_sin_nota')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="precio_con_nota" value="Precio Con Nota *" />
                            <x-text-input id="precio_con_nota" type="number" step="0.01" min="0" name="precio_con_nota" 
                                          :value="old('precio_con_nota', $examen->precio_con_nota)" required class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('precio_con_nota')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">El precio con nota debe ser mayor que el precio sin nota.</p>
                        </div>

                        @isset($clinicas)
                        <details class="mt-6 border border-gray-200 rounded-lg" {{ old('precios_clinicas') ? 'open' : '' }}>
                            <summary class="px-4 py-3 bg-gray-50 cursor-pointer text-sm font-semibold text-gray-700 hover:bg-gray-100 rounded-lg">
                                Precios por Clínica (opcional)
                            </summary>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 mb-3">Dejar vacío para usar el precio global del examen.</p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Clínica</th>
                                                <th class="px-3 py-2 text-right font-medium text-gray-700">Precio Sin Nota</th>
                                                <th class="px-3 py-2 text-right font-medium text-gray-700">Precio Con Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($clinicas as $clinica)
                                                <tr class="border-b border-gray-100">
                                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $clinica->nombre }}</td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="0.01" min="0" max="999999.99"
                                                               name="precios_clinicas[{{ $clinica->id }}][sin_nota]"
                                                               value="{{ old('precios_clinicas.'.$clinica->id.'.sin_nota', $examen->clinicas()->where('clinica_id', $clinica->id)->first()?->pivot?->precio_sin_nota ?? '') }}"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                                                               placeholder="Global">
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="0.01" min="0" max="999999.99"
                                                               name="precios_clinicas[{{ $clinica->id }}][con_nota]"
                                                               value="{{ old('precios_clinicas.'.$clinica->id.'.con_nota', $examen->clinicas()->where('clinica_id', $clinica->id)->first()?->pivot?->precio_con_nota ?? '') }}"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                                                               placeholder="Global">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                        @endisset

                        <div class="flex items-center justify-end mt-6 space-x-4">
                            <a href="{{ route('examenes.index') }}" class="text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <x-primary-button>
                                Actualizar Precios
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
