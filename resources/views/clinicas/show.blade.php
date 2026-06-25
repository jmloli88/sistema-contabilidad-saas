<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalles de Clínica
            </h2>
            <div>
                <a href="{{ route('clinicas.edit', $clinica) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Editar
                </a>
                <a href="{{ route('clinicas.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Nombre</h3>
                        <p class="text-gray-900">{{ $clinica->nombre }}</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Dirección</h3>
                        <p class="text-gray-900">{{ $clinica->direccion ?? 'No especificada' }}</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Teléfono</h3>
                        <p class="text-gray-900">{{ $clinica->telefono ?? 'No especificado' }}</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Fecha de Registro</h3>
                        <p class="text-gray-900">{{ $clinica->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Última Actualización</h3>
                        <p class="text-gray-900">{{ $clinica->updated_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="border-t pt-6">
                        <form action="{{ route('clinicas.destroy', $clinica) }}" method="POST" data-confirm="¿Está seguro de eliminar esta clínica? Esta acción no se puede deshacer.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Eliminar Clínica
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
