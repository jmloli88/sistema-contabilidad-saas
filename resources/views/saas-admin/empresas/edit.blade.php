<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
            {{ __('Editar Empresa') }}: {{ $empresa->nombre }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    <form method="POST" action="{{ route('saas.admin.empresas.update', $empresa->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Empresa</label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $empresa->nombre) }}"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   required>
                            @error('nombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Actualizar Empresa
                            </button>
                            <a href="{{ route('saas.admin.empresas.index') }}"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
