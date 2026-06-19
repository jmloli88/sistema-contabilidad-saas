<x-saas-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: #191c22;">
            {{ __('Historial de Suscripciones') }}: {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('saas.admin.index') }}" class="text-blue-600 hover:text-blue-900 hover:underline">
                    &larr; Volver al panel
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    @if($subscriptions->isEmpty())
                        <p class="text-sm" style="color: #727784;">Este usuario no tiene registros de suscripción.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stripe ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicio</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($subscriptions as $sub)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                                {{ $sub->type }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($sub->stripe_status === 'active')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                                @elseif($sub->stripe_status === 'canceled')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelado</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ $sub->stripe_status }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono" style="color: #414753;">
                                                {{ $sub->stripe_id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                                {{ $sub->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                                {{ $sub->ends_at ? $sub->ends_at->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: #414753;">
                                                {{ $sub->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-saas-layout>
