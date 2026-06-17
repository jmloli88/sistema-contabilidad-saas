<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #1a1a2e;">
            {{ __('Dashboard SaaS') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Total Usuarios -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Total Usuarios</p>
                            <p class="text-3xl font-bold" style="color: #1a1a2e;">{{ $totalUsers }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(79, 70, 229, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #4f46e5; font-variation-settings: 'FILL' 1;">group</span>
                        </div>
                    </div>
                </div>

                <!-- Clínicas Activas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Clínicas Activas</p>
                            <p class="text-3xl font-bold text-indigo-600">{{ $activeClinics }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(99, 102, 241, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #6366f1; font-variation-settings: 'FILL' 1;">business</span>
                        </div>
                    </div>
                </div>

                <!-- Suscripciones Activas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Suscripciones Activas</p>
                            <p class="text-3xl font-bold text-green-600">{{ $activeCount }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(34, 197, 94, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #22c55e; font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                    </div>
                </div>

                <!-- Suscripciones Expiradas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Suscripciones Expiradas</p>
                            <p class="text-3xl font-bold text-red-600">{{ $expiredCount }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(239, 68, 68, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #ef4444; font-variation-settings: 'FILL' 1;">warning</span>
                        </div>
                    </div>
                </div>

                <!-- Por Vencer -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Por Vencer (≤7 días)</p>
                            <p class="text-3xl font-bold text-yellow-600">{{ $expiringSoon }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(234, 179, 8, 0.1);">
                            <span class="material-symbols-outlined text-2xl" style="color: #eab308; font-variation-settings: 'FILL' 1;">schedule</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MRR Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-2xl shadow-lg p-6 mb-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-200 text-sm font-medium mb-1">Ingreso Mensual Estimado (MRR)</p>
                        <p class="text-4xl font-bold">R$ {{ number_format($estimatedMRR, 0, ',', '.') }}</p>
                        <p class="text-indigo-200 text-xs mt-1">Basado en R$50 por suscripción activa</p>
                    </div>
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center bg-white/20">
                        <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                    </div>
                </div>
            </div>

            <!-- Usuarios Recientes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold" style="color: #1a1a2e;">Usuarios Recientes</h3>
                    <a href="{{ route('saas.admin.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                        Ver todos
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clínica</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vence</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentUsers as $u)
                                @php
                                    $sub = $u->subscription('default');
                                    $status = $sub?->stripe_status ?? 'none';
                                    $endsAt = $sub?->ends_at;
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm" style="color: #727784;">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold mb-4" style="color: #1a1a2e;">Acciones Rápidas</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('saas.admin.index') }}" 
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium text-white transition-all duration-200 hover:opacity-90"
                       style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">group</span>
                        Ver Todos los Usuarios
                    </a>
                    <a href="{{ route('billing.index') }}" 
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all duration-200 hover:opacity-90"
                       style="background-color: #f1f5f9; color: #1a1a2e;"
                       onmouseover="this.style.backgroundColor='#e2e8f0';"
                       onmouseout="this.style.backgroundColor='#f1f5f9';">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">credit_card</span>
                        Ir a Facturación
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
