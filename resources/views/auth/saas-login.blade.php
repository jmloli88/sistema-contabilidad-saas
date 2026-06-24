<x-guest-layout>
    <div x-data="{ showPassword: false, loading: false }" class="w-full">
        <!-- Título -->
        <div class="mb-8 text-center">
            <img src="/logo.png" alt="VictCorp" class="w-20 h-auto mx-auto mb-4 drop-shadow-[0_0_20px_rgba(255,255,255,0.1)]">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Acceso Administradores SaaS</h2>
            <p class="text-gray-500 text-sm">Panel de administración de la plataforma</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('saas.login') }}" class="space-y-5" @submit="loading = true">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block mb-1.5 text-sm font-medium text-gray-700">Correo Electrónico</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <svg class="w-5 h-5 {{ $errors->has('email') ? 'text-red-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                    </div>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border transition-colors duration-200 
                            {{ $errors->has('email') ? 'border-red-300 bg-red-50 focus:ring-red-200 focus:border-red-400' : 'border-gray-200 bg-gray-50 focus:ring-cyan-200 focus:border-cyan-400' }}"
                        placeholder="admin@contamed.com">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block mb-1.5 text-sm font-medium text-gray-700">Contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <svg class="w-5 h-5 {{ $errors->has('password') ? 'text-red-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                        class="w-full pl-11 pr-12 py-2.5 text-sm rounded-xl border transition-colors duration-200
                            {{ $errors->has('password') ? 'border-red-300 bg-red-50 focus:ring-red-200 focus:border-red-400' : 'border-gray-200 bg-gray-50 focus:ring-cyan-200 focus:border-cyan-400' }}"
                        placeholder="••••••••">
                    <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 transition-colors"
                            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 text-sm font-semibold text-white bg-gradient-to-r from-blue-900 to-cyan-600 hover:from-blue-950 hover:to-cyan-700 focus:ring-4 focus:ring-cyan-200 rounded-xl transition-all duration-200 disabled:opacity-70 disabled:cursor-wait shadow-sm">
                <svg x-show="loading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span x-text="loading ? 'Ingresando...' : 'Ingresar al Panel SaaS'">Ingresar al Panel SaaS</span>
            </button>

            <!-- Link back to system login -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    ¿Sos usuario del sistema?
                    <a href="{{ route('login') }}" class="font-semibold text-cyan-600 hover:text-cyan-500 transition-colors">
                        Ingresá por acá
                    </a>
                </p>
            </div>
        </form>
    </div>
</x-guest-layout>
