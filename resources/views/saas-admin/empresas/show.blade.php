<x-saas-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
                {{ $empresa->nombre }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('saas.admin.empresas.edit', $empresa->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-all duration-200 hover:opacity-90"
                   style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">edit</span>
                    Editar
                </a>
                <a href="{{ route('saas.admin.empresas.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200"
                   style="background-color: #f1f5f9; color: #1a1a2e;">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Usuarios -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Usuarios</p>
                            <p class="text-3xl font-bold" style="color: #1a1a2e;">{{ $empresa->users_count }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(79, 70, 229, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #4f46e5; font-variation-settings: 'FILL' 1;">group</span>
                        </div>
                    </div>
                </div>

                <!-- Clínicas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Clínicas</p>
                            <p class="text-3xl font-bold text-indigo-600">{{ $empresa->clinicas_count }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(99, 102, 241, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #6366f1; font-variation-settings: 'FILL' 1;">business</span>
                        </div>
                    </div>
                </div>

                <!-- Exámenes -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Exámenes</p>
                            <p class="text-3xl font-bold text-green-600">{{ $empresa->examenes_count }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(34, 197, 94, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #22c55e; font-variation-settings: 'FILL' 1;">biotech</span>
                        </div>
                    </div>
                </div>

                <!-- Estado Suscripción -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Suscripción</p>
                            @if($activeSubscription)
                                <p class="text-3xl font-bold text-green-600">Activa</p>
                            @else
                                <p class="text-3xl font-bold text-gray-400">—</p>
                            @endif
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $activeSubscription ? 'rgba(34, 197, 94, 0.1)' : 'rgba(156, 163, 175, 0.1)' }};">
                            <span class="material-symbols-outlined text-2xl" style="color: {{ $activeSubscription ? '#22c55e' : '#9ca3af' }}; font-variation-settings: 'FILL' 1;">
                                {{ $activeSubscription ? 'check_circle' : 'remove_circle' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información General -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4" style="color: #1a1a2e;">Información General</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                        <dd class="mt-1 text-sm" style="color: #191c22;">{{ $empresa->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Creada</dt>
                        <dd class="mt-1 text-sm" style="color: #191c22;">{{ $empresa->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Última actualización</dt>
                        <dd class="mt-1 text-sm" style="color: #191c22;">{{ $empresa->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Usuarios de la Empresa -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold" style="color: #1a1a2e;">Usuarios ({{ $empresa->users_count }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suscripción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($empresa->users as $u)
                                @php
                                    $sub = $u->subscription('default');
                                    $isActive = $sub && $sub->ends_at && $sub->ends_at->isFuture() && $sub->stripe_status === 'active';
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($isActive)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Sin suscripción
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm" style="color: #727784;">
                                        No hay usuarios en esta empresa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
