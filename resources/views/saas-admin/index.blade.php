<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
            {{ __('Panel de Administración SaaS') }}
        </h2>
    </x-slot>

    <div x-data="userEditor" class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clínica</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vence</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $u)
                                    @php
                                        $sub = $u->subscription('default');
                                        $status = $sub?->stripe_status ?? 'none';
                                        $endsAt = $sub?->ends_at;
                                        $isExpired = $endsAt && $endsAt->isPast();
                                        $isActive = $endsAt && $endsAt->isFuture();
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: #191c22;">
                                            {{ $u->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $u->email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $u->role }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $u->clinica?->nombre ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($status === 'none')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Sin suscripción
                                                </span>
                                            @elseif($isActive)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Expirado
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                            {{ $endsAt ? $endsAt->format('d/m/Y') : '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-0.5">
                                                {{-- Editar --}}
                                                <button type="button" title="Editar usuario"
                                                        @click="editUser({ id: {{ $u->id }}, name: @js($u->name), email: @js($u->email), role: @js($u->role), clinica_id: {{ $u->clinica_id ?? 'null' }} })"
                                                        class="p-1.5 rounded hover:bg-indigo-50 text-indigo-500 transition-colors">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </button>
                                                {{-- Extender +30d --}}
                                                <form action="{{ route('saas.admin.extend', $u) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Extender +30 días"
                                                            class="p-1.5 rounded hover:bg-blue-50 text-blue-600 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">add_circle</span>
                                                    </button>
                                                </form>
                                                {{-- Fecha manual --}}
                                                <form action="{{ route('saas.admin.expiry', $u) }}" method="POST" class="inline-flex items-center">
                                                    @csrf
                                                    <input type="date" name="expires_at" value="{{ $endsAt?->format('Y-m-d') }}" 
                                                           title="Vencimiento manual"
                                                           class="text-xs border-gray-200 rounded w-28 px-1 py-0.5 focus:border-indigo-400 focus:ring-0" />
                                                    <button type="submit" title="Guardar fecha"
                                                            class="p-1 rounded hover:bg-green-50 text-green-600 transition-colors">
                                                        <span class="material-symbols-outlined text-base">check</span>
                                                    </button>
                                                </form>
                                                {{-- Historial --}}
                                                <a href="{{ route('saas.admin.history', $u) }}" title="Historial"
                                                   class="p-1.5 rounded hover:bg-gray-100 text-gray-500 transition-colors">
                                                    <span class="material-symbols-outlined text-lg">history</span>
                                                </a>
                                                {{-- Cancelar --}}
                                                <form action="{{ route('saas.admin.cancel', $u) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('¿Cancelar suscripción de {{ $u->name }}?')">
                                                    @csrf
                                                    <button type="submit" title="Cancelar suscripción"
                                                            class="p-1.5 rounded hover:bg-red-50 text-red-500 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">cancel</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm" style="color: #727784;">
                                            No hay usuarios registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit User Modal --}}
        <div x-show="open" x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center"
             style="display: none;">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close()"></div>
            {{-- Modal --}}
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold" style="color: #191c22;">Editar Usuario</h3>
                    <button @click="close()" class="p-1 rounded hover:bg-gray-100 text-gray-400">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form :action="'/saas/admin/' + userId + '/update'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" x-model="userName" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" x-model="userEmail" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" x-model="userRole" required
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="administrador">Administrador</option>
                            <option value="usuario">Usuario</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Clínica</label>
                        <select name="clinica_id" x-model="userClinicaId"
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Sin clínica —</option>
                            @foreach($clinicas as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close()"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('userEditor', () => ({
                open: false,
                userId: null,
                userName: '',
                userEmail: '',
                userRole: 'usuario',
                userClinicaId: null,
                editUser(data) {
                    this.userId = data.id;
                    this.userName = data.name;
                    this.userEmail = data.email;
                    this.userRole = data.role;
                    this.userClinicaId = data.clinica_id;
                    this.open = true;
                },
                close() {
                    this.open = false;
                }
            }));
        });
    </script>
    @endpush
</x-saas-layout>
