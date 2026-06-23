<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Facturación / Suscripción') }}
        </h2>
    </x-slot>

    <div class="py-8 md:py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @php
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

            {{-- Active subscription status banner --}}
            @if ($isActive && !$isExpired)
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="border-l-4 border-green-500 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                <span class="text-sm font-semibold uppercase tracking-wider text-green-700">
                                    Activo — {{ $subscriptionType === 'premium' ? 'PREMIUM' : 'STANDARD' }}
                                </span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">
                                {{ $subscriptionType === 'premium' ? 'Plan PREMIUM' : 'Plan STANDARD' }}
                            </h3>
                            @if ($endsAt)
                                <p class="mt-1 text-gray-500">Vence el <strong>{{ $endsAt->format('d/m/Y') }}</strong> · {{ $daysRemaining }} días restantes</p>
                            @endif
                        </div>
                        <span class="material-symbols-outlined text-5xl text-green-100">verified</span>
                    </div>
                </div>
            </div>

            @elseif ($isExpired && $subscriptionType)
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="border-l-4 border-red-500 p-6">
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

            {{-- Two plan cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                {{-- STANDARD --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col {{ $subscriptionType === 'standard' && $isActive ? 'ring-2 ring-indigo-500' : '' }}">
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex-1">
                            <span class="material-symbols-outlined text-3xl text-gray-400 mb-2 block">star</span>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">STANDARD</h3>
                            <div class="flex items-baseline gap-1 mb-4">
                                <span class="text-3xl font-extrabold text-gray-900">R$50</span>
                                <span class="text-gray-500 text-sm">/mes</span>
                            </div>
                            <ul class="space-y-2 text-sm text-gray-600 mb-6">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-green-500">check</span>
                                Usuarios ilimitados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-green-500">check</span>
                                Clínicas ilimitadas
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-green-500">check</span>
                                Exámenes personalizables
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-green-500">check</span>
                                Reportes avanzados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-green-500">check</span>
                                Calendario de agendas
                            </li>
                            <li class="flex items-center gap-2 text-gray-400">
                                <span class="material-symbols-outlined text-base">close</span>
                                Chat con IA
                            </li>
                            <li class="flex items-center gap-2 text-gray-400">
                                <span class="material-symbols-outlined text-base">close</span>
                                Chat por Telegram
                            </li>
                            <li class="flex items-center gap-2 text-gray-400">
                                <span class="material-symbols-outlined text-base">close</span>
                                Sync Google Calendar
                            </li>
                        </ul>
                        </div>

                        @if ($subscriptionType === 'standard' && $isActive)
                            <div class="w-full py-3 text-center bg-indigo-50 text-indigo-700 font-semibold rounded-xl text-sm mt-auto">
                                ✅ Plan actual
                            </div>
                        @else
                            <button onclick="handlePay('standard')"
                                class="w-full py-3 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-xl transition-colors text-sm mt-auto">
                                {{ $isExpired ? 'Renovar' : 'Activar' }} STANDARD — R$ 50/mes
                            </button>
                        @endif
                    </div>
                </div>

                {{-- PREMIUM --}}
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl shadow-lg overflow-hidden flex flex-col {{ $subscriptionType === 'premium' && $isActive ? 'ring-2 ring-indigo-300 ring-offset-2' : '' }}">
                    <div class="p-6 text-white flex flex-col flex-1">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                            <span class="text-xs font-semibold uppercase tracking-wider bg-indigo-500 px-2 py-0.5 rounded-full">Recomendado</span>
                        </div>
                        <h3 class="text-lg font-bold mb-1">PREMIUM</h3>
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-3xl font-extrabold">R$90</span>
                            <span class="text-indigo-200 text-sm">/mes</span>
                        </div>
                        <ul class="space-y-2 text-sm text-indigo-100 mb-6">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-indigo-300">check</span>
                                Todo lo de Standard
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-indigo-300">check</span>
                                Chat con IA interno
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-indigo-300">check</span>
                                Chat por Telegram
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-indigo-300">check</span>
                                Sync Google Calendar
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-indigo-300">check</span>
                                Soporte prioritario
                            </li>
                        </ul>
                        </div>

                        @if ($subscriptionType === 'premium' && $isActive)
                            <div class="w-full py-3 text-center bg-white/20 text-white font-semibold rounded-xl text-sm border border-white/30 mt-auto">
                                ✅ Plan actual
                            </div>
                        @else
                            <button onclick="handlePay('premium')"
                                class="w-full py-3 bg-white hover:bg-indigo-50 text-indigo-700 font-semibold rounded-xl transition-colors text-sm shadow-sm mt-auto">
                                {{ $isExpired ? 'Renovar' : 'Activar' }} PREMIUM — R$ 90/mes
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment form (hidden by default, shown when a plan is clicked) --}}
            <div id="card-payment-section" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hidden mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Datos de la tarjeta</h3>
                    <p class="text-sm text-gray-500 mb-4">Pago único de <strong id="payment-amount">R$ 50,00</strong> por 30 días de acceso</p>

                    <div id="card-element" class="p-4 bg-gray-50 border border-gray-200 rounded-lg"></div>
                    <div id="card-errors" class="mt-2 text-sm text-red-600 hidden"></div>

                    <div class="mt-5 flex flex-col sm:flex-row gap-3">
                        <button id="card-submit-btn"
                            class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm">
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">lock</span>
                                <span id="payment-btn-text">Pagar R$ 50,00</span>
                            </span>
                        </button>
                        <button id="card-cancel-btn"
                            class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium rounded-xl hover:bg-gray-100 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Payment history --}}
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
                                    <p class="text-xs text-gray-500">
                                        R$ {{ $payment->stripe_price === 'price_premium' ? '90,00' : '50,00' }}
                                        — {{ $payment->type === 'premium' ? 'PREMIUM' : ($payment->type === 'standard' ? 'STANDARD' : $payment->type) }}
                                    </p>
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

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        let currentPlan = 'standard';

        function handlePay(plan) {
            currentPlan = plan;
            const section = document.getElementById('card-payment-section');
            const amountEl = document.getElementById('payment-amount');
            const btnText = document.getElementById('payment-btn-text');

            section.classList.remove('hidden');
            section.scrollIntoView({ behavior: 'smooth', block: 'center' });

            const isPremium = plan === 'premium';
            amountEl.textContent = isPremium ? 'R$ 90,00' : 'R$ 50,00';
            btnText.textContent = isPremium ? 'Pagar R$ 90,00' : 'Pagar R$ 50,00';

            if (!window.cardMounted) {
                window.stripe = Stripe('{{ $stripeKey }}');
                window.elements = window.stripe.elements();
                window.cardElement = window.elements.create('card', {
                    style: { base: { fontSize: '16px', fontFamily: '"Figtree", system-ui, sans-serif' } },
                    hidePostalCode: true
                });
                window.cardElement.mount('#card-element');
                window.cardMounted = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            window.cardMounted = false;

            const cardErrors = document.getElementById('card-errors');
            const submitBtn = document.getElementById('card-submit-btn');
            const cancelBtn = document.getElementById('card-cancel-btn');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    document.getElementById('card-payment-section').classList.add('hidden');
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
                            body: JSON.stringify({ plan: currentPlan }),
                        });

                        if (!response.ok) throw new Error('Payment failed');

                        const data = await response.json();

                        if (data.client_secret) {
                            const { error, paymentIntent } = await window.stripe.confirmCardPayment(data.client_secret, {
                                payment_method: { card: window.cardElement },
                            });

                            if (error) {
                                cardErrors.textContent = error.message;
                                cardErrors.classList.remove('hidden');
                            } else if (paymentIntent.status === 'succeeded') {
                                await fetch('{{ route('billing.pay') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Confirm-Payment': paymentIntent.id },
                                    body: JSON.stringify({ plan: currentPlan }),
                                });
                                window.location.reload();
                            }
                        }
                    } catch (err) {
                        cardErrors.textContent = 'Error al procesar el pago. Intente nuevamente.';
                        cardErrors.classList.remove('hidden');
                    } finally {
                        submitBtn.disabled = false;
                        const isPremium = currentPlan === 'premium';
                        submitBtn.innerHTML = '<span class="flex items-center justify-center gap-2"><span class="material-symbols-outlined text-lg">lock</span> ' + (isPremium ? 'Pagar R$ 90,00' : 'Pagar R$ 50,00') + '</span>';
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
