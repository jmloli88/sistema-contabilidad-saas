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
</section>
