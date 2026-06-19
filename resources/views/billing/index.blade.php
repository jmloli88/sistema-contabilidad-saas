<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Facturación / Suscripción') }}
        </h2>
    </x-slot>

    <div class="py-8 md:py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @php
                $showPayment = !$isActive || $isExpired || ($daysRemaining > 0 && $daysRemaining <= 7);
                $user = auth()->user();
                $empresaName = $user->empresa?->nombre;
                $stripeKey = config('services.stripe.key');
            @endphp

            {{-- Empresa banner --}}
            @if($empresaName)
            <div class="mb-6 flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-indigo-600 text-xl">business</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $empresaName }}</p>
                    <p class="text-xs text-gray-500">Suscripción empresarial — todos los usuarios comparten el acceso</p>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- Columna principal: Estado + Pago --}}
                <div class="lg:col-span-3 space-y-6">

                    {{-- Estado de suscripción --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        @if ($isActive && !$isExpired && $daysRemaining > 7)
                            {{-- Activo --}}
                            <div class="border-l-4 border-green-500">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                                <span class="text-sm font-semibold uppercase tracking-wider text-green-700">Activo</span>
                                            </div>
                                            <h3 class="text-2xl font-bold text-gray-900">Suscripción al día</h3>
                                            @if ($endsAt)
                                                <p class="mt-1 text-gray-500">Vence el <strong>{{ $endsAt->format('d/m/Y') }}</strong></p>
                                            @endif
                                        </div>
                                        <span class="material-symbols-outlined text-5xl text-green-100">verified</span>
                                    </div>
                                    <div class="mt-4 w-full bg-gray-100 rounded-full h-2">
                                        @php $pct = $endsAt ? min(100, max(0, 100 - ($daysRemaining / 30 * 100))) : 100; @endphp
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-500">{{ $daysRemaining }} días restantes de 30</p>
                                </div>
                            </div>

                        @elseif ($isActive && $daysRemaining > 0 && $daysRemaining <= 7)
                            {{-- Por vencer --}}
                            <div class="border-l-4 border-amber-500">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span class="text-sm font-semibold uppercase tracking-wider text-amber-700">Por vencer</span>
                                            </div>
                                            <h3 class="text-2xl font-bold text-gray-900">Quedan {{ $daysRemaining }} días</h3>
                                            @if ($endsAt)
                                                <p class="mt-1 text-gray-500">Vence el <strong>{{ $endsAt->format('d/m/Y') }}</strong></p>
                                            @endif
                                        </div>
                                        <span class="material-symbols-outlined text-5xl text-amber-100">schedule</span>
                                    </div>
                                    <div class="mt-4 w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, ($daysRemaining / 7) * 100) }}%"></div>
                                    </div>
                                </div>
                            </div>

                        @elseif (!$subscription)
                            {{-- Sin suscripción --}}
                            <div class="border-l-4 border-gray-400">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                                                <span class="text-sm font-semibold uppercase tracking-wider text-gray-600">Sin suscripción</span>
                                            </div>
                                            <h3 class="text-2xl font-bold text-gray-900">Activá tu cuenta</h3>
                                            <p class="mt-1 text-gray-500">R$ 50,00 por mes. Acceso ilimitado para toda tu empresa.</p>
                                        </div>
                                        <span class="material-symbols-outlined text-5xl text-gray-100">credit_card_off</span>
                                    </div>
                                </div>
                            </div>

                        @else
                            {{-- Expirado --}}
                            <div class="border-l-4 border-red-500">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                                <span class="text-sm font-semibold uppercase tracking-wider text-red-700">Expirado</span>
                                            </div>
                                            <h3 class="text-2xl font-bold text-gray-900">Suscripción vencida</h3>
                                            @if ($endsAt)
                                                <p class="mt-1 text-gray-500">Vencida desde el <strong>{{ $endsAt->format('d/m/Y') }}</strong></p>
                                            @endif
                                        </div>
                                        <span class="material-symbols-outlined text-5xl text-red-100">error</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Botón de pago --}}
                        @if ($showPayment)
                        <div class="px-6 pb-6">
                            <button id="pay-with-pix"
                                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm hover:shadow-md">
                                <span class="material-symbols-outlined text-xl">credit_card</span>
                                @if (!$subscription)
                                    Activar suscripción — R$ 50,00/mes
                                @elseif ($isExpired)
                                    Renovar suscripción — R$ 50,00/mes
                                @else
                                    Pagar con Tarjeta
                                @endif
                            </button>
                        </div>
                        @endif
                    </div>

                    {{-- Formulario de pago --}}
                    @if ($showPayment)
                    <div id="card-payment-section" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">Datos de la tarjeta</h3>
                            <p class="text-sm text-gray-500 mb-4">Pago único de R$ 50,00 por 30 días de acceso</p>

                            <div id="card-element" class="p-4 bg-gray-50 border border-gray-200 rounded-lg"></div>
                            <div id="card-errors" class="mt-2 text-sm text-red-600 hidden"></div>

                            <div class="mt-5 flex flex-col sm:flex-row gap-3">
                                <button id="card-submit-btn"
                                    class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm">
                                    <span class="flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-lg">lock</span>
                                        Pagar R$ 50,00
                                    </span>
                                </button>
                                <button id="card-cancel-btn"
                                    class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium rounded-xl hover:bg-gray-100 transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Sidebar: Plan + Historial --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Plan info --}}
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                        <span class="material-symbols-outlined text-3xl mb-3 block">workspace_premium</span>
                        <h3 class="text-lg font-bold mb-1">Plan Empresarial</h3>
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="text-3xl font-extrabold">R$50</span>
                            <span class="text-indigo-200 text-sm">/mes</span>
                        </div>
                        <ul class="space-y-2 text-sm text-indigo-100">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">check</span>
                                Usuarios ilimitados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">check</span>
                                Clínicas ilimitadas
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">check</span>
                                Exámenes personalizables
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">check</span>
                                Reportes avanzados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">check</span>
                                Soporte prioritario
                            </li>
                        </ul>
                    </div>

                    {{-- Historial --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-900">Historial de Pagos</h3>
                        </div>
                        <div class="p-4">
                            @if ($paymentHistory->isEmpty())
                                <div class="text-center py-6">
                                    <span class="material-symbols-outlined text-3xl text-gray-300 mb-2 block">receipt_long</span>
                                    <p class="text-sm text-gray-500">No hay pagos registrados</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($paymentHistory as $payment)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $payment->created_at->format('d/m/Y') }}</p>
                                            <p class="text-xs text-gray-500">R$ 50,00</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                            {{ $payment->stripe_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $payment->stripe_status === 'active' ? 'Pagado' : $payment->stripe_status }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const stripe = Stripe('{{ $stripeKey }}');
            const elements = stripe.elements();
            const cardElement = elements.create('card', { 
                style: { base: { fontSize: '16px', fontFamily: '"Figtree", system-ui, sans-serif' } },
                hidePostalCode: true
            });
            const payButton = document.getElementById('pay-with-pix');
            const cardSection = document.getElementById('card-payment-section');
            const cardErrors = document.getElementById('card-errors');
            const submitBtn = document.getElementById('card-submit-btn');
            const cancelBtn = document.getElementById('card-cancel-btn');
            let cardMounted = false;

            if (payButton && cardSection) {
                payButton.addEventListener('click', function () {
                    cardSection.classList.remove('hidden');
                    cardSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (!cardMounted) {
                        cardElement.mount('#card-element');
                        cardMounted = true;
                    }
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    cardSection.classList.add('hidden');
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', async function () {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Procesando...</span>';
                    cardErrors.classList.add('hidden');

                    try {
                        const response = await fetch('{{ route('billing.pay') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });

                        if (!response.ok) throw new Error('Payment failed');

                        const data = await response.json();

                        if (data.client_secret) {
                            const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
                                payment_method: { card: cardElement },
                            });

                            if (error) {
                                cardErrors.textContent = error.message;
                                cardErrors.classList.remove('hidden');
                            } else if (paymentIntent.status === 'succeeded') {
                                await fetch('{{ route('billing.pay') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Confirm-Payment': paymentIntent.id },
                                });
                                window.location.reload();
                            }
                        }
                    } catch (err) {
                        cardErrors.textContent = 'Error al procesar el pago. Intente nuevamente.';
                        cardErrors.classList.remove('hidden');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="flex items-center justify-center gap-2"><span class="material-symbols-outlined text-lg">lock</span> Pagar R$ 50,00</span>';
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
