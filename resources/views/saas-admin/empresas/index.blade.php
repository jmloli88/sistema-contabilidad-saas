<x-saas-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
                {{ __('Empresas') }}
            </h2>
            <a href="{{ route('saas.admin.empresas.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-all duration-200 hover:opacity-90"
               style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">add</span>
                Nueva Empresa
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuarios</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clínicas</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creada</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($empresas as $emp)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: #191c22;">
                                            {{ $emp->nombre }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $emp->users_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $emp->clinicas_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $emp->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-1">
                                                <a href="{{ route('saas.admin.empresas.show', $emp->id) }}"
                                                   class="p-1.5 rounded hover:bg-indigo-50 text-indigo-500 transition-colors"
                                                   title="Ver detalle">
                                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                                </a>
                                                <a href="{{ route('saas.admin.empresas.edit', $emp->id) }}"
                                                   class="p-1.5 rounded hover:bg-blue-50 text-blue-600 transition-colors"
                                                   title="Editar">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </a>
                                                <form action="{{ route('saas.admin.empresas.destroy', $emp->id) }}"
                                                      method="POST" class="inline"
                                                      onsubmit="return confirm('¿Eliminar la empresa {{ $emp->nombre }}? Esta acción no se puede deshacer si tiene datos asociados.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Eliminar"
                                                            class="p-1.5 rounded hover:bg-red-50 text-red-500 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm" style="color: #727784;">
                                            No hay empresas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $empresas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
