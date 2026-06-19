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
         class="fixed inset-0 bg-black/40 z-20 lg:hidden">
    </div>

    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 px-4 py-3 flex items-center justify-between bg-[#1a1a2e] border-b border-white/10">
        <button @click="open = !open" class="p-2 rounded-lg text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-lg font-bold text-slate-200">ContaMed SaaS</span>
        <div class="w-10"></div>
    </div>

    <!-- Sidebar -->
    <nav :class="{'translate-x-0': open, '-translate-x-full': !open}"
         class="fixed top-0 left-0 z-30 h-full w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col bg-[#1a1a2e] border-r border-white/5">
        
        <!-- Logo Section -->
        <div class="px-5 py-5 flex items-center gap-3 border-b border-white/10">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg bg-gradient-to-br from-indigo-600 to-indigo-400">
                <span class="material-symbols-outlined fill text-xl">admin_panel_settings</span>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-bold tracking-tight text-slate-100 leading-tight">ContaMed SaaS</h1>
                <p class="text-[10px] font-medium text-slate-500">Panel de Administración</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto py-5 px-3">
            <!-- Navegación Section -->
            <div class="px-3 mb-2 text-[11px] font-bold uppercase tracking-widest text-slate-500">Navegación</div>
            
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('saas.admin.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('saas.admin.dashboard') ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('saas.admin.dashboard') ? 'fill' : '' }}">dashboard</span>
                    <span class="font-medium text-sm">Dashboard</span>
                    @if(request()->routeIs('saas.admin.dashboard'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    @endif
                </a>

                <!-- Usuarios -->
                <a href="{{ route('saas.admin.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('saas.admin.index') ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('saas.admin.index') ? 'fill' : '' }}">group</span>
                    <span class="font-medium text-sm">Usuarios</span>
                    @if(request()->routeIs('saas.admin.index'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    @endif
                </a>

                <!-- Empresas -->
                <a href="{{ route('saas.admin.empresas.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('saas.admin.empresas*') ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('saas.admin.empresas*') ? 'fill' : '' }}">business</span>
                    <span class="font-medium text-sm">Empresas</span>
                    @if(request()->routeIs('saas.admin.empresas*'))
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    @endif
                </a>
            </div>
        </div>

        <!-- User Profile Section -->
        <div class="p-4 border-t border-white/10">
            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white/5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 bg-gradient-to-br from-indigo-600 to-indigo-400">
                    {{ substr(Auth::guard('saas')->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate text-slate-100">{{ Auth::guard('saas')->user()->name }}</p>
                    <p class="text-[10px] truncate text-slate-500">{{ Auth::guard('saas')->user()->email }}</p>
                </div>
            </div>
            
            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('saas.logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 text-slate-400 hover:bg-red-500/10 hover:text-red-400">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>
