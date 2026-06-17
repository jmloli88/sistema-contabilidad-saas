<x-guest-layout>
    <!-- Título -->
    <div class="mb-8 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white shadow-lg mx-auto mb-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
            <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">admin_panel_settings</span>
        </div>
        <h2 class="text-3xl font-bold mb-2" style="color: #1a1a2e;">Acceso Administradores SaaS</h2>
        <p class="text-gray-600">Panel de administración de la plataforma</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('saas.login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block mb-2 text-sm font-medium" style="color: #1a1a2e;">Correo Electrónico</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                </div>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-11 p-3" 
                    placeholder="admin@contamed.com">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block mb-2 text-sm font-medium" style="color: #1a1a2e;">Contraseña</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-11 p-3" 
                    placeholder="••••••••">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full text-white font-medium rounded-lg text-sm px-5 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-200 hover:opacity-90" 
                style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
            <span class="flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Ingresar al Panel SaaS
            </span>
        </button>

        <!-- Link back to system login -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                ¿Sos usuario del sistema?
                <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    Ingresá por acá
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
