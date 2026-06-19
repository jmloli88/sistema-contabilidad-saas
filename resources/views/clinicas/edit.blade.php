<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Clínica
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('clinicas.update', $clinica) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-5">
                            <x-input-label for="nombre" :value="__('Nombre')" />
                            <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre', $clinica->nombre)" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div class="mb-5">
                            <x-input-label for="direccion" :value="__('Dirección')" />
                            <textarea id="direccion" name="direccion" rows="3" class="border-gray-200 focus:ring-2 focus:ring-indigo-200 rounded-xl shadow-sm block mt-1 w-full transition-all duration-200">{{ old('direccion', $clinica->direccion) }}</textarea>
                            <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                        </div>

                        <div class="mb-5">
                            <x-input-label for="telefono" :value="__('Teléfono')" />
                            <x-text-input id="telefono" class="block mt-1 w-full" type="text" name="telefono" :value="old('telefono', $clinica->telefono)" />
                            <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-5">
                            <a href="{{ route('clinicas.index') }}" class="text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl w-full sm:w-auto font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md">
                                Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
