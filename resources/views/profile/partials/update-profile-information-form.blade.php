<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Información del Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Actualiza la información de perfil y dirección de correo electrónico de tu cuenta.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>

    <!-- Telegram Link Section -->
    <div class="mt-6 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Vincular Telegram') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('Vinculá tu cuenta de Telegram para usar el bot de consultas.') }}</p>

        @if (session('status') === 'telegram-linked')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 3000)"
                class="mt-2 text-sm font-medium text-green-600"
            >{{ __('¡Cuenta de Telegram vinculada!') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.link-telegram') }}" class="mt-3 flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <x-input-label for="telegram_auth_token" :value="__('Código del bot')" />
                <x-text-input
                    id="telegram_auth_token"
                    name="telegram_auth_token"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Ingresá el código del bot"
                />
                <x-input-error class="mt-2" :messages="$errors->get('telegram_auth_token')" />
            </div>
            <x-primary-button>{{ __('Vincular') }}</x-primary-button>
        </form>
    </div>

    @if (auth()->user()?->role === 'administrador')
    <!-- Google Calendar Integration Section -->
    <div class="mt-6 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Google Calendar') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('Sincronizá las agendas de tu empresa con Google Calendar (y automáticamente con iOS).') }}</p>

        <div x-data="googleCalendar" class="mt-3">
            <div class="flex items-center gap-3">
                <template x-if="status === 'connected'">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1 text-sm text-green-700 bg-green-50 px-3 py-1 rounded-full">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Conectado — <span x-text="email"></span>
                        </span>
                        <form method="POST" action="{{ route('google-calendar.sync') }}" class="inline">
                            @csrf
                            <x-secondary-button type="submit" title="Sincronizar todas las agendas ahora">{{ __('Sincronizar ahora') }}</x-secondary-button>
                        </form>
                        <form method="POST" action="{{ route('google-calendar.disconnect') }}" class="inline">
                            @csrf
                            <x-secondary-button type="submit">{{ __('Desconectar') }}</x-secondary-button>
                        </form>
                    </div>
                </template>
                <template x-if="status === 'disconnected'">
                    <a href="{{ route('google-calendar.redirect') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        {{ __('Conectar con Google Calendar') }}
                    </a>
                </template>
                <template x-if="status === 'loading'">
                    <span class="text-sm text-gray-500">Verificando...</span>
                </template>
            </div>
        </div>
    </div>
    @endif
</section>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('googleCalendar', () => ({
            status: 'loading',
            email: '',

            async init() {
                try {
                    const res = await fetch('{{ route('google-calendar.status') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.status = data.connected ? 'connected' : 'disconnected';
                        this.email = data.google_email ?? '';
                    } else {
                        this.status = 'disconnected';
                    }
                } catch (e) {
                    this.status = 'disconnected';
                }
            },
        }));
    });
</script>
@endpush
