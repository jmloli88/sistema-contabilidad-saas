<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Facturación / Suscripción') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $showPayment = !$isActive || $isExpired || ($daysRemaining > 0 && $daysRemaining <= 7);
                $user = auth()->user();
                $clinicName = $user->clinica?->nombre;
            @endphp

            @if($clinicName)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-blue-600 mr-2">business</span>
                        <span class="text-blue-800 font-medium text-sm">
                            Esta suscripción cubre a todos los usuarios de <strong>{{ $clinicName }}</strong>.
                            El administrador de la clínica gestiona el pago.
                        </span>
                    </div>
                </div>
            @endif

            <!-- Subscription Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Estado de la Suscripción') }}</h3>

                    @if ($isActive && !$isExpired && (!$daysRemaining || $daysRemaining > 7))
                        {{-- Active subscription with more than 7 days left --}}
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-green-600 mr-2">check_circle</span>
                                <span class="text-green-800 font-medium">{{ __('Activo') }}</span>
                            </div>
                            @if ($endsAt)
                                <p class="mt-2 text-green-700">
                                    {{ __('Tu suscripción está activa. Vence el') }}
                                    {{ $endsAt->format('d/m/Y') }}.
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 text-sm text-gray-500">
                            <p>{{ __('Tu suscripción está al día. No es necesario realizar ningún pago en este momento.') }}</p>
                        </div>

                    @elseif ($isActive && $daysRemaining > 0 && $daysRemaining <= 7)
                        {{-- Active but ending soon (within 7 days) --}}
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-yellow-600 mr-2">schedule</span>
                                <span class="text-yellow-800 font-medium">{{ __('Vence en :days días.', ['days' => $daysRemaining]) }}</span>
                            </div>
                            @if ($endsAt)
                                <p class="mt-2 text-yellow-700">
                                    {{ __('Tu suscripción vence el :date. Renueva para mantener el acceso.', ['date' => $endsAt->format('d/m/Y')]) }}
                                </p>
                            @endif
                            <div class="mt-4">
                                <button id="pay-with-pix"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Pagar con PIX') }}
                                </button>
                            </div>
                        </div>

                    @elseif (!$subscription)
                        {{-- No subscription --}}
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-gray-400 mr-2">info</span>
                                <span class="text-gray-700 font-medium">{{ __('Sin suscripción activa') }}</span>
                            </div>
                            <p class="mt-2 text-gray-600">
                                {{ __('Aún no tienes una suscripción activa. Para acceder a todas las funcionalidades, adquiere tu suscripción por R$ 50,00/mes.') }}
                            </p>
                            <div class="mt-4">
                                <button id="pay-with-pix"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Pagar con PIX') }}
                                </button>
                            </div>
                        </div>

                    @else
                        {{-- Expired subscription --}}
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-red-600 mr-2">error</span>
                                <span class="text-red-800 font-medium">{{ __('Expirado') }}</span>
                            </div>
                            <p class="mt-2 text-red-700">
                                {{ __('Tu suscripción ha expirado. Renueva para seguir accediendo a todas las funcionalidades.') }}
                            </p>
                            @if ($endsAt)
                                <p class="mt-1 text-red-600 text-sm">
                                    {{ __('Vencida desde el :date', ['date' => $endsAt->format('d/m/Y')]) }}.
                                </p>
                            @endif
                            <div class="mt-4">
                                <button id="pay-with-pix"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Renovar por R$ 50/mes') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- PIX Payment Section (hidden when active and not ending soon) -->
            @if ($showPayment)
            <div id="pix-payment-section" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hidden">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Pagar con PIX') }}</h3>

                    <div id="pix-loading" class="text-center py-8">
                        <span class="material-symbols-outlined text-4xl text-indigo-600 animate-spin">refresh</span>
                        <p class="mt-4 text-gray-600">{{ __('Generando código PIX...') }}</p>
                    </div>

                    <div id="pix-qr-section" class="hidden text-center">
                        <div id="pix-qr-code" class="mb-4 inline-block p-4 bg-white border rounded-lg">
                            {{-- QR code image rendered here --}}
                        </div>
                        <div class="max-w-md mx-auto">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Código PIX (copiar y pegar)') }}</label>
                            <div class="flex">
                                <input id="pix-copy-paste" type="text" readonly
                                       class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <button id="pix-copy-btn"
                                        class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-sm text-gray-700 hover:bg-gray-200">
                                    {{ __('Copiar') }}
                                </button>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button id="check-payment-btn"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Ya pagué — Verificar') }}
                            </button>
                        </div>
                        <div id="pix-error" class="mt-4 text-red-600 text-sm hidden"></div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Historial de Pagos') }}</h3>

                    @if ($paymentHistory->isEmpty())
                        <p class="text-gray-500 text-sm">{{ __('No hay pagos registrados.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Monto') }}</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($paymentHistory as $payment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment['date'] ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment['amount'] ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($payment['status'] ?? '') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $payment['status'] ?? '' }}
                                            </span>
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

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payButton = document.getElementById('pay-with-pix');
            const pixSection = document.getElementById('pix-payment-section');
            const pixLoading = document.getElementById('pix-loading');
            const pixQrSection = document.getElementById('pix-qr-section');
            const pixQrCode = document.getElementById('pix-qr-code');
            const pixCopyPaste = document.getElementById('pix-copy-paste');
            const pixCopyBtn = document.getElementById('pix-copy-btn');
            const pixError = document.getElementById('pix-error');
            const checkPaymentBtn = document.getElementById('check-payment-btn');

            if (payButton) {
                payButton.addEventListener('click', async function () {
                    if (pixSection) pixSection.classList.remove('hidden');
                    if (pixLoading) pixLoading.classList.remove('hidden');
                    if (pixQrSection) pixQrSection.classList.add('hidden');
                    if (pixError) pixError.classList.add('hidden');

                    try {
                        const response = await fetch('{{ route('billing.pay') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Payment failed');
                        }

                        const data = await response.json();

                        if (data.client_secret) {
                            const stripe = Stripe('{{ config('services.stripe.key') }}');

                            // For PIX, we use the client_secret and Stripe.confirmPayment
                            const { error } = await stripe.confirmPayment({
                                elements: stripe.elements(),
                                confirmParams: {
                                    return_url: '{{ route('billing.index') }}',
                                },
                                redirect: 'if_required',
                            });

                            if (error) {
                                if (pixError) {
                                    pixError.textContent = error.message;
                                    pixError.classList.remove('hidden');
                                }
                            }
                        }

                        if (pixLoading) pixLoading.classList.add('hidden');
                        if (pixQrSection) pixQrSection.classList.remove('hidden');

                    } catch (err) {
                        if (pixLoading) pixLoading.classList.add('hidden');
                        if (pixError) {
                            pixError.textContent = '{{ __('Error al procesar el pago. Intente nuevamente.') }}';
                            pixError.classList.remove('hidden');
                        }
                    }
                });
            }

            // Copy to clipboard
            if (pixCopyBtn) {
                pixCopyBtn.addEventListener('click', function () {
                    if (pixCopyPaste) {
                        pixCopyPaste.select();
                        document.execCommand('copy');
                        pixCopyBtn.textContent = '{{ __('Copiado') }}';
                        setTimeout(() => {
                            pixCopyBtn.textContent = '{{ __('Copiar') }}';
                        }, 2000);
                    }
                });
            }

            // Check payment status by reloading
            if (checkPaymentBtn) {
                checkPaymentBtn.addEventListener('click', function () {
                    window.location.reload();
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
