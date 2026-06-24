<!-- Sidebar Navigation -->
<aside x-data="{ open: false }" class="relative">
    <!-- Mobile Overlay -->
    <div x-show="open" 
         @click="open = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-20 lg:hidden">
    </div>

    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 px-4 py-3 flex items-center justify-between bg-white border-b border-gray-100">
        <button @click="open = !open" class="p-2 rounded-lg text-gray-600 hover:bg-cyan-50 hover:text-cyan-700 transition-colors">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <div class="flex items-center gap-2">
            <img src="/logo.png" alt="VictCorp" class="w-8 h-auto">
            <span class="text-lg font-bold text-gray-900">Contabilidad</span>
        </div>
        <div class="w-10"></div>
    </div>

    <!-- Sidebar -->
    <nav :class="{'translate-x-0': open, '-translate-x-full': !open}"
         class="fixed top-0 left-0 z-30 h-full w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col bg-white border-r border-gray-100">
        
        <!-- Logo Section -->
        <div class="px-5 py-5 flex items-center gap-3 border-b border-gray-100">
            <img src="/logo.png" alt="VictCorp" class="w-12 h-auto">
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold tracking-tight text-gray-900 leading-tight">Contabilidad</h1>
                @if(Auth::user()->empresa_id)
                    <p class="text-[11px] font-medium truncate text-cyan-500">{{ Auth::user()->empresa->nombre }}</p>
                @endif
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto py-5 px-3">
            <!-- Principal Section -->
            <div class="px-3 mb-2 text-[11px] font-bold uppercase tracking-widest text-cyan-400">Principal</div>
            
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('dashboard') ? 'fill' : '' }}">dashboard</span>
                    <span class="font-medium text-sm">{{ __('Dashboard') }}</span>
                    @if(request()->routeIs('dashboard'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>

                <!-- Clínicas -->
                <a href="{{ route('clinicas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('clinicas.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('clinicas.*') ? 'fill' : '' }}">account_balance</span>
                    <span class="font-medium text-sm">{{ __('Clínicas') }}</span>
                    @if(request()->routeIs('clinicas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>

                <!-- Repases -->
                <a href="{{ route('repases.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('repases.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('repases.*') ? 'fill' : '' }}">payments</span>
                    <span class="font-medium text-sm">{{ __('Repases') }}</span>
                    @if(request()->routeIs('repases.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>

                <!-- Calendario Repases -->
                <a href="{{ route('calendario.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*') ? 'fill' : '' }}">calendar_month</span>
                    <span class="font-medium text-sm">{{ __('Calendario Repases') }}</span>
                    @if(request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>

                <!-- Agendas -->
                <a href="{{ route('agendas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('agendas.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('agendas.*') ? 'fill' : '' }}">event_note</span>
                    <span class="font-medium text-sm">{{ __('Agendas') }}</span>
                    @if(request()->routeIs('agendas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>

                <!-- Facturación -->
                <a href="{{ route('billing.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('billing.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('billing.*') ? 'fill' : '' }}">credit_card</span>
                    <span class="font-medium text-sm">{{ __('Facturación') }}</span>
                    @if(request()->routeIs('billing.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    @endif
                </a>
            </div>

            <!-- Admin Section -->
            @if(Auth::user()->isAdmin())
                <div class="mt-6 mb-2 px-3 text-[11px] font-bold uppercase tracking-widest text-cyan-400">Administración</div>
                
                <div class="space-y-1">
                    <!-- Reportes -->
                    <a href="{{ route('reportes.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('reportes.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('reportes.*') ? 'fill' : '' }}">analytics</span>
                        <span class="font-medium text-sm">{{ __('Reportes') }}</span>
                        @if(request()->routeIs('reportes.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        @endif
                    </a>

                    <!-- Balances -->
                    <a href="{{ route('balances.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('balances.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('balances.*') ? 'fill' : '' }}">account_balance_wallet</span>
                        <span class="font-medium text-sm">{{ __('Balances') }}</span>
                        @if(request()->routeIs('balances.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        @endif
                    </a>

                    <!-- Precios -->
                    <a href="{{ route('examenes.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('examenes.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('examenes.*') ? 'fill' : '' }}">sell</span>
                        <span class="font-medium text-sm">{{ __('Precios') }}</span>
                        @if(request()->routeIs('examenes.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        @endif
                    </a>

                    <!-- Usuarios -->
                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('users.*') ? 'bg-cyan-50 text-cyan-600' : 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-700' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('users.*') ? 'fill' : '' }}">group</span>
                        <span class="font-medium text-sm">{{ __('Usuarios') }}</span>
                        @if(request()->routeIs('users.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        @endif
                    </a>
                </div>
            @endif
        </div>

        <!-- User Profile Section -->
        <div class="p-4 border-t border-gray-100">
            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 bg-gradient-to-br from-cyan-400 to-blue-500">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate text-gray-900">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] truncate text-gray-400">{{ Auth::user()->email }}</p>
                </div>
            </div>
            
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 text-gray-600 hover:bg-cyan-50 hover:text-cyan-700">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    {{ __('Perfil') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 text-gray-600 hover:bg-red-50 hover:text-red-600">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        {{ __('Salir') }}
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>
