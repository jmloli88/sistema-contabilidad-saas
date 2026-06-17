<!-- Sidebar Navigation - Clinical Architect Design -->
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
         class="fixed inset-0 bg-black/40 z-20 lg:hidden"
         style="display: none;">
    </div>

    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 px-4 py-3 flex items-center justify-between" style="background-color: #f9f9ff; border-bottom: 1px solid rgba(193, 198, 213, 0.15);">
        <button @click="open = !open" class="p-2 rounded-lg transition-colors" style="color: #414753;" onmouseover="this.style.backgroundColor='rgba(0,90,182,0.05)'; this.style.color='#005ab6'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#414753'">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-lg font-bold" style="color: #191c22;">Contabilidad</span>
        <div class="w-10"></div>
    </div>

    <!-- Sidebar -->
    <nav :class="{'translate-x-0': open, '-translate-x-full': !open}"
         class="fixed top-0 left-0 z-30 h-full w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col"
         style="background-color: #f9f9ff; border-right: 1px solid rgba(193, 198, 213, 0.15);">
        
        <!-- Logo Section -->
        <div class="px-5 py-5 flex items-center gap-3" style="border-bottom: 1px solid rgba(193, 198, 213, 0.15);">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg" style="background: linear-gradient(135deg, #005ab6 0%, #1d73dc 100%);">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">account_balance</span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold tracking-tight" style="color: #191c22; line-height: 1.2;">Contabilidad</h1>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto py-5 px-3">
            <!-- Principal Section -->
            <div class="px-3 mb-2 text-[11px] font-bold uppercase tracking-widest" style="color: #727784;">Principal</div>
            
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('dashboard') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('dashboard') ? 1 : 0 }};">dashboard</span>
                    <span class="font-medium text-sm">{{ __('Dashboard') }}</span>
                    @if(request()->routeIs('dashboard'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>

                <!-- Clínicas -->
                <a href="{{ route('clinicas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('clinicas.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('clinicas.*') ? 1 : 0 }};">account_balance</span>
                    <span class="font-medium text-sm">{{ __('Clínicas') }}</span>
                    @if(request()->routeIs('clinicas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>

                <!-- Repases -->
                <a href="{{ route('repases.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('repases.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('repases.*') ? 1 : 0 }};">payments</span>
                    <span class="font-medium text-sm">{{ __('Repases') }}</span>
                    @if(request()->routeIs('repases.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>

                <!-- Calendario Repases -->
                <a href="{{ route('calendario.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*') ? 1 : 0 }};">calendar_month</span>
                    <span class="font-medium text-sm">{{ __('Calendario Repases') }}</span>
                    @if(request()->routeIs('calendario.*') && !request()->routeIs('calendario.agendas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>

                <!-- Agendas -->
                <a href="{{ route('agendas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('agendas.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('agendas.*') ? 1 : 0 }};">event_note</span>
                    <span class="font-medium text-sm">{{ __('Agendas') }}</span>
                    @if(request()->routeIs('agendas.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>

                <!-- Facturación -->
                <a href="{{ route('billing.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('billing.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('billing.*') ? 1 : 0 }};">credit_card</span>
                    <span class="font-medium text-sm">{{ __('Facturación') }}</span>
                    @if(request()->routeIs('billing.*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                    @endif
                </a>
            </div>

            <!-- Admin Section -->
            @if(Auth::user()->isAdmin())
                <div class="mt-6 mb-2 px-3 text-[11px] font-bold uppercase tracking-widest" style="color: #727784;">Administración</div>
                
                <div class="space-y-1">
                    <!-- Reportes -->
                    <a href="{{ route('reportes.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                       style="{{ request()->routeIs('reportes.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                       onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                       onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('reportes.*') ? 1 : 0 }};">analytics</span>
                        <span class="font-medium text-sm">{{ __('Reportes') }}</span>
                        @if(request()->routeIs('reportes.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                        @endif
                    </a>

                    <!-- Balances -->
                    <a href="{{ route('balances.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                       style="{{ request()->routeIs('balances.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                       onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                       onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('balances.*') ? 1 : 0 }};">account_balance_wallet</span>
                        <span class="font-medium text-sm">{{ __('Balances') }}</span>
                        @if(request()->routeIs('balances.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                        @endif
                    </a>

                    <!-- Predictivo -->
                     <!--
                    <a href="{{ route('predictivo.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                       style="{{ request()->routeIs('predictivo.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                       onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                       onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('predictivo.*') ? 1 : 0 }};">insights</span>
                        <span class="font-medium text-sm">{{ __('Predictivo') }}</span>
                        @if(request()->routeIs('predictivo.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                        @endif
                    </a>
                    -->

                    <!-- Precios -->
                    <a href="{{ route('examenes.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                       style="{{ request()->routeIs('examenes.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                       onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                       onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('examenes.*') ? 1 : 0 }};">sell</span>
                        <span class="font-medium text-sm">{{ __('Precios') }}</span>
                        @if(request()->routeIs('examenes.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                        @endif
                    </a>

                    <!-- Usuarios -->
                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                       style="{{ request()->routeIs('users.*') ? 'background: linear-gradient(135deg, rgba(0, 90, 182, 0.08) 0%, rgba(29, 115, 220, 0.08) 100%); color: #005ab6;' : 'color: #414753;' }}"
                       onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22'; }"
                       onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#414753'; }">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('users.*') ? 1 : 0 }};">group</span>
                        <span class="font-medium text-sm">{{ __('Usuarios') }}</span>
                        @if(request()->routeIs('users.*'))
                            <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #005ab6;"></span>
                        @endif
                    </a>
                </div>
            @endif

        </div>

        <!-- User Profile Section -->
        <div class="p-4" style="border-top: 1px solid rgba(193, 198, 213, 0.15);">
            <div class="flex items-center gap-3 p-2.5 rounded-xl" style="background-color: #ffffff; box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.04); border: 1px solid rgba(193, 198, 213, 0.15);">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background: linear-gradient(135deg, #005ab6 0%, #1d73dc 100%);">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate" style="color: #191c22;">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] truncate" style="color: #727784;">{{ Auth::user()->email }}</p>
                </div>
            </div>
            
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200"
                   style="color: #414753;"
                   onmouseover="this.style.backgroundColor='rgba(0, 90, 182, 0.04)'; this.style.color='#191c22';"
                   onmouseout="this.style.backgroundColor='transparent'; this.style.color='#414753';">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    {{ __('Perfil') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200"
                            style="color: #414753;"
                            onmouseover="this.style.backgroundColor='rgba(186, 26, 26, 0.04)'; this.style.color='#ba1a1a';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#414753';">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        {{ __('Salir') }}
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>
