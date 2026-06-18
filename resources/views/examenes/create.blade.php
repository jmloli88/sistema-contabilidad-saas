<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Examen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('examenes.store') }}" method="POST">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="nombre" value="Nombre del Examen *" />
                            <x-text-input id="nombre" type="text" name="nombre" 
                                          :value="old('nombre')" required class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <x-input-label for="precio_sin_nota" value="Precio Sin Nota *" />
                                <x-text-input id="precio_sin_nota" type="number" step="0.01" min="0" name="precio_sin_nota" 
                                              :value="old('precio_sin_nota')" required class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('precio_sin_nota')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="precio_con_nota" value="Precio Con Nota *" />
                                <x-text-input id="precio_con_nota" type="number" step="0.01" min="0" name="precio_con_nota" 
                                              :value="old('precio_con_nota')" required class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('precio_con_nota')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">El precio con nota debe ser mayor que el precio sin nota.</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('examenes.index') }}" class="text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <x-primary-button>
                                Guardar Examen
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
