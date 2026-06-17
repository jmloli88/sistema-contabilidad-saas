<!-- Sidebar Navigation - SaaS Admin Corporate Design -->
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
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 px-4 py-3 flex items-center justify-between" style="background-color: #1a1a2e; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <button @click="open = !open" class="p-2 rounded-lg transition-colors" style="color: #e2e8f0;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-lg font-bold" style="color: #e2e8f0;">ContaMed SaaS</span>
        <div class="w-10"></div>
    </div>

    <!-- Sidebar -->
    <nav :class="{'translate-x-0': open, '-translate-x-full': !open}"
         class="fixed top-0 left-0 z-30 h-full w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col"
         style="background-color: #1a1a2e; border-right: 1px solid rgba(255,255,255,0.05);">
        
        <!-- Logo Section -->
        <div class="px-5 py-5 flex items-center gap-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">admin_panel_settings</span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold tracking-tight" style="color: #f1f5f9; line-height: 1.2;">ContaMed SaaS</h1>
                <p class="text-[10px] font-medium" style="color: #94a3b8;">Panel de Administración</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto py-5 px-3">
            <!-- Navegación Section -->
            <div class="px-3 mb-2 text-[11px] font-bold uppercase tracking-widest" style="color: #64748b;">Navegación</div>
            
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('saas.admin.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('saas.admin.dashboard') ? 'background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%); color: #818cf8;' : 'color: #94a3b8;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(255,255,255,0.05)'; this.style.color='#e2e8f0'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#94a3b8'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('saas.admin.dashboard') ? 1 : 0 }};">dashboard</span>
                    <span class="font-medium text-sm">Dashboard</span>
                    @if(request()->routeIs('saas.admin.dashboard'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #818cf8;"></span>
                    @endif
                </a>

                <!-- Usuarios -->
                <a href="{{ route('saas.admin.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('saas.admin.index') ? 'background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%); color: #818cf8;' : 'color: #94a3b8;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(255,255,255,0.05)'; this.style.color='#e2e8f0'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#94a3b8'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('saas.admin.index') ? 1 : 0 }};">group</span>
                    <span class="font-medium text-sm">Usuarios</span>
                    @if(request()->routeIs('saas.admin.index'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #818cf8;"></span>
                    @endif
                </a>

                <!-- Empresas -->
                <a href="{{ route('saas.admin.empresas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group"
                   style="{{ request()->routeIs('saas.admin.empresas*') ? 'background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%); color: #818cf8;' : 'color: #94a3b8;' }}"
                   onmouseover="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='rgba(255,255,255,0.05)'; this.style.color='#e2e8f0'; }"
                   onmouseout="if(!this.style.background.includes('gradient')) { this.style.backgroundColor='transparent'; this.style.color='#94a3b8'; }">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ request()->routeIs('saas.admin.empresas*') ? 1 : 0 }};">business</span>
                    <span class="font-medium text-sm">Empresas</span>
                    @if(request()->routeIs('saas.admin.empresas*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full" style="background-color: #818cf8;"></span>
                    @endif
                </a>

            </div>
        </div>

        <!-- User Profile Section -->
        <div class="p-4" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <div class="flex items-center gap-3 p-2.5 rounded-xl" style="background-color: rgba(255,255,255,0.05);">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                    {{ substr(Auth::guard('saas')->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate" style="color: #f1f5f9;">{{ Auth::guard('saas')->user()->name }}</p>
                    <p class="text-[10px] truncate" style="color: #94a3b8;">{{ Auth::guard('saas')->user()->email }}</p>
                </div>
            </div>
            
            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('saas.logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200"
                            style="color: #94a3b8;"
                            onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#94a3b8';">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>
