<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
            {{ __('Panel de Administración SaaS') }}
        </h2>
    </x-slot>

    <div x-data="userEditor" class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Empresa Filter -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <form method="GET" class="flex items-center gap-4">
                        <label for="empresa_id" class="text-sm font-medium text-gray-700">Filtrar por empresa:</label>
                        <select name="empresa_id" id="empresa_id" onchange="this.form.submit()"
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Todas las empresas —</option>
                            @foreach($empresas as $emp)
                                <option value="{{ $emp->id }}" {{ request('empresa_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @if(request('empresa_id'))
                            <a href="{{ route('saas.admin.index') }}" class="text-xs text-indigo-600 hover:underline">
                                Limpiar filtro
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold" style="color: #191c22;">Usuarios</h3>
                    <button type="button" @click="createUser()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        Nuevo Usuario
                    </button>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vence</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $u)
                                    @php
                                        $empresaSub = $u->empresa ? $u->empresa->activeSubscription() : null;
                                        $status = $empresaSub?->stripe_status ?? 'none';
                                        $endsAt = $empresaSub?->ends_at;
                                        $isActive = $endsAt && $endsAt->isFuture();
                                        $planType = $u->empresa ? $u->empresa->activeSubscriptionType() : null;
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
                                            {{ $u->empresa?->nombre ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($status === 'none')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Sin suscripción
                                                </span>
                                            @elseif($isActive)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Activo — {{ strtoupper($planType ?? '') }}
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
                                                        @click="editUser({ id: {{ $u->id }}, name: @js($u->name), email: @js($u->email), role: @js($u->role), empresa_id: {{ $u->empresa_id ?? 'null' }} })"
                                                        class="p-1.5 rounded-xl hover:bg-indigo-50 text-indigo-500 transition-colors">
                                                    <span class="material-symbols-outlined text-lg">edit</span>
                                                </button>
                                                {{-- Extender +30d --}}
                                                <form action="{{ route('saas.admin.extend', $u) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Extender +30 días"
                                                            class="p-1.5 rounded-xl hover:bg-blue-50 text-blue-600 transition-colors">
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
                                                            class="p-1 rounded-xl hover:bg-green-50 text-green-600 transition-colors">
                                                        <span class="material-symbols-outlined text-base">check</span>
                                                    </button>
                                                </form>
                                                {{-- Historial --}}
                                                <a href="{{ route('saas.admin.history', $u) }}" title="Historial"
                                                   class="p-1.5 rounded-xl hover:bg-gray-100 text-gray-500 transition-colors">
                                                    <span class="material-symbols-outlined text-lg">history</span>
                                                </a>
                                                {{-- Cancelar --}}
                                                <form action="{{ route('saas.admin.cancel', $u) }}" method="POST" class="inline"
                                                      data-confirm="¿Cancelar suscripción de {{ $u->name }}?">
                                                    @csrf
                                                    <button type="submit" title="Cancelar suscripción"
                                                            class="p-1.5 rounded-xl hover:bg-red-50 text-red-500 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">cancel</span>
                                                    </button>
                                                </form>
                                                {{-- Upgrade / Downgrade plan --}}
                                                @if($planType === 'standard')
                                                <form action="{{ route('saas.admin.plan', $u) }}" method="POST" class="inline"
                                                      data-confirm="¿Actualizar suscripción de {{ $u->name }} a PREMIUM?">
                                                    @csrf
                                                    <input type="hidden" name="plan" value="premium">
                                                    <button type="submit" title="Actualizar a PREMIUM"
                                                            class="p-1.5 rounded-xl hover:bg-amber-50 text-amber-500 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">upgrade</span>
                                                    </button>
                                                </form>
                                                @elseif($planType === 'premium')
                                                <form action="{{ route('saas.admin.plan', $u) }}" method="POST" class="inline"
                                                      data-confirm="¿Bajar suscripción de {{ $u->name }} a STANDARD?">
                                                    @csrf
                                                    <input type="hidden" name="plan" value="standard">
                                                    <button type="submit" title="Bajar a STANDARD"
                                                            class="p-1.5 rounded-xl hover:bg-slate-50 text-slate-400 transition-colors">
                                                        <span class="material-symbols-outlined text-lg">keyboard_double_arrow_down</span>
                                                    </button>
                                                </form>
                                                @endif
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
                    <h3 class="text-lg font-semibold" style="color: #191c22;" x-text="isEditing ? 'Editar Usuario' : 'Nuevo Usuario'"></h3>
                    <button @click="close()" class="p-1 rounded-xl hover:bg-gray-100 text-gray-400">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form :action="isEditing ? '/saas/admin/' + userId + '/update' : '/saas/admin/usuarios'" method="POST" class="space-y-4">
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
                    <div x-show="!isEditing">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" required x-show="!isEditing"
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
                        <label class="block text-xs font-medium text-gray-700 mb-1">Empresa</label>
                        <select name="empresa_id" x-model="userEmpresaId"
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Sin empresa —</option>
                            @foreach($empresas as $e)
                                <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close()"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-xl hover:bg-indigo-700"
                                x-text="isEditing ? 'Guardar cambios' : 'Crear usuario'">
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
                isEditing: false,
                userId: null,
                userName: '',
                userEmail: '',
                userRole: 'usuario',
                userEmpresaId: null,
                createUser() {
                    this.isEditing = false;
                    this.userId = null;
                    this.userName = '';
                    this.userEmail = '';
                    this.userRole = 'usuario';
                    this.userEmpresaId = null;
                    this.open = true;
                },
                editUser(data) {
                    this.isEditing = true;
                    this.userId = data.id;
                    this.userName = data.name;
                    this.userEmail = data.email;
                    this.userRole = data.role;
                    this.userEmpresaId = data.empresa_id;
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
