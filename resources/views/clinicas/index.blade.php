<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-sky-600 text-xl fill">account_balance</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Clínicas
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Gestiona las clínicas del sistema</p>
                </div>
            </div>
            @if(Auth::user()->isAdmin())
                <button onclick="openClinicaModal()" class="inline-flex items-center justify-center text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 font-semibold rounded-xl text-sm px-5 py-2.5 min-h-[44px] focus:outline-none transition-all duration-200 shadow-sm hover:shadow-md whitespace-nowrap touch-manipulation">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Nueva Clínica
                </button>
            @endif
        </div>
    </x-slot>

    <div x-data="clinicaModal()" class="min-h-screen bg-gradient-to-br from-cyan-50/30 via-white to-blue-50/30">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @if($clinicas->isEmpty())
                    <!-- Estado vacío -->
                    <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl mx-4 sm:mx-0">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay clínicas</h3>
                            <p class="mt-1 text-sm text-gray-500">Comienza registrando una nueva clínica.</p>
                            @if(Auth::user()->isAdmin())
                                <div class="mt-6">
                                    <button onclick="openClinicaModal()" class="inline-flex items-center text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus:ring-4 focus:ring-cyan-200 font-semibold rounded-xl text-sm px-6 py-3 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Nueva Clínica
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Vista de Tabla (Desktop) -->
                    <div class="hidden md:block bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 font-bold">Nombre</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Dirección</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Teléfono</th>
                                        <th scope="col" class="px-6 py-4 font-bold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($clinicas as $clinica)
                                        <tr class="bg-white/50 border-b border-gray-200 hover:bg-cyan-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-cyan-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="font-medium text-gray-900">{{ $clinica->nombre }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center text-gray-700">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    {{ $clinica->direccion ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center text-gray-700">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                                    </svg>
                                                    {{ $clinica->telefono ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <!-- Botón Ver -->
                                                    <a href="{{ route('clinicas.show', $clinica) }}" 
                                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation text-blue-600"
                                                       title="Ver detalles">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </a>
                                                    @if(Auth::user()->isAdmin())
                                                        <!-- Botón Editar -->
                                                        <a href="{{ route('clinicas.edit', $clinica) }}" 
                                                           class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation text-cyan-600"
                                                           title="Editar">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                            </svg>
                                        </button>
                                                        <!-- Botón Eliminar -->
                                                        <form action="{{ route('clinicas.destroy', $clinica) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 touch-manipulation text-red-600"
                                                                    title="Eliminar"
                                                                    onclick="return confirm('¿Está seguro de eliminar esta clínica?')">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
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
                        @foreach($clinicas as $clinica)
                            <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
                                <div class="p-4">
                                    <!-- Header de la tarjeta -->
                                    <div class="mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $clinica->nombre }}</h3>
                                    </div>

                                    <!-- Detalles -->
                                    <div class="space-y-2 mb-4">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-gray-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <span class="text-sm text-gray-500 block">Dirección</span>
                                                <span class="text-sm text-gray-900">{{ $clinica->direccion ?? 'No especificada' }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-gray-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <span class="text-sm text-gray-500 block">Teléfono</span>
                                                <span class="text-sm text-gray-900">{{ $clinica->telefono ?? 'No especificado' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex gap-2 pt-3 border-t border-gray-200">
                                        <a href="{{ route('clinicas.show', $clinica) }}" class="flex-1 text-center text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                            Ver
                                        </a>
                                        @if(Auth::user()->isAdmin())
                                            <a href="{{ route('clinicas.edit', $clinica) }}" class="flex-1 text-center text-white bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                                Editar
                                            </a>
                                            <form action="{{ route('clinicas.destroy', $clinica) }}" method="POST" class="flex-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-white bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 focus:ring-4 focus:ring-red-200 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none" onclick="return confirm('¿Está seguro de eliminar esta clínica?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6">
                        {{ $clinicas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Nueva Clínica -->
    <div id="clinicaModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <button type="button" onclick="closeClinicaModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
            <h3 class="text-lg font-bold text-gray-900 mb-4">Nueva Clínica</h3>
            <form id="clinicaForm" class="space-y-4">
                @csrf
                <div>
                    <label for="modal_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" id="modal_nombre" name="nombre" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-200 focus:border-cyan-400 transition-colors">
                    <p id="modal-nombre-error" class="mt-1 text-xs text-red-600 hidden"></p>
                </div>
                <div>
                    <label for="modal_direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="modal_direccion" name="direccion" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-200 focus:border-cyan-400 transition-colors"></textarea>
                </div>
                <div>
                    <label for="modal_telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" id="modal_telefono" name="telefono"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-cyan-200 focus:border-cyan-400 transition-colors">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeClinicaModal()" class="px-5 py-2.5 text-sm font-semibold text-cyan-700 bg-white border border-cyan-200 rounded-xl hover:bg-cyan-50 transition-colors">Cancelar</button>
                    <button type="submit" id="modal-save-btn" class="px-5 py-2.5 text-sm font-semibold text-white bg-cyan-500 rounded-xl hover:bg-cyan-600 transition-colors shadow-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openClinicaModal() {
            document.getElementById('clinicaModal').classList.remove('hidden');
        }
        function closeClinicaModal() {
            document.getElementById('clinicaModal').classList.add('hidden');
            document.getElementById('modal-nombre-error').classList.add('hidden');
        }
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('clinicaForm');
            if (!form) return;
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = document.getElementById('modal-save-btn');
                btn.disabled = true;
                btn.textContent = 'Guardando...';
                try {
                    const res = await fetch('{{ route("clinicas.store") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ nombre: document.getElementById('modal_nombre').value, direccion: document.getElementById('modal_direccion').value, telefono: document.getElementById('modal_telefono').value }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.errors?.nombre) {
                            const errEl = document.getElementById('modal-nombre-error');
                            errEl.textContent = data.errors.nombre[0];
                            errEl.classList.remove('hidden');
                        } else {
                            alert(data.message || 'Error al guardar.');
                        }
                        return;
                    }
                    closeClinicaModal();
                    window.location.reload();
                } catch (e) {
                    alert('Error de conexión. Intentá de nuevo.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Guardar';
                }
            });
        });
    </script>
</x-app-layout>
