<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
            {{ __('Nueva Empresa') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('saas.admin.empresas.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Empresa</label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Ej: Clínica Nueva Era" required>
                            @error('nombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Crear Empresa
                            </button>
                            <a href="{{ route('saas.admin.empresas.index') }}"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
