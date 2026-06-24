<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-cyan-600 text-xl fill">group</span>
            </div>
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Gestión de Usuarios</h2>
                <p class="text-sm text-gray-500 mt-0.5">Administrá los usuarios y permisos</p>
            </div>
        </div>
    </x-slot>

    <div x-data="userManager" class="py-12 min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Botón Nuevo Usuario (dentro del scope Alpine) --}}
            <div class="flex justify-end mb-4">
                <button @click="openCreate = true"
                        class="inline-flex items-center justify-center text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-semibold rounded-xl text-sm px-5 py-2.5 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"></path></svg>
                    Nuevo Usuario
                </button>
            </div>
            @if($users->isEmpty())
                <div class="bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg shadow-xl">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay usuarios</h3>
                        <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo usuario.</p>
                    </div>
                </div>
            @else
                <div class="hidden md:block bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold">Nombre</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Email</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Rol</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                    <tr class="bg-white/50 border-b border-gray-200 hover:bg-indigo-50/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $u->name }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $u->email }}</td>
                                        <td class="px-6 py-4">
                                            @if($u->role === 'administrador')
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800">
                                                    <span class="material-symbols-outlined text-sm">shield</span>
                                                    Administrador
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800">
                                                    <span class="material-symbols-outlined text-sm">person</span>
                                                    Usuario
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <button @click="openEdit(@js($u->id), @js($u->name), @js(strstr($u->email, '@', true)), @js($u->role))"
                                                         class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-cyan-600 transition-colors duration-200" title="Editar">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                                                </button>
                                                @if($u->id !== auth()->id())
                                                    <x-confirm-modal message="¿Está seguro de eliminar este usuario?" action="{{ route('users.destroy', $u) }}" method="DELETE">
                                                        <button type="button" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-red-600 transition-colors duration-200" title="Eliminar">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                        </button>
                                                    </x-confirm-modal>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile cards -->
                <div class="md:hidden space-y-4 px-4">
                    @foreach($users as $u)
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $u->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $u->email }}</p>
                            <span class="text-xs font-medium px-2 py-0.5 rounded mt-1 inline-block {{ $u->role === 'administrador' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($u->role) }}</span>
                            <div class="flex gap-2 mt-3 pt-3 border-t">
                                <button @click="openEdit(@js($u->id), @js($u->name), @js(strstr($u->email, '@', true)), @js($u->role))"
                                        class="flex-1 text-center text-white bg-cyan-600 hover:bg-cyan-700 rounded-lg text-sm px-4 py-2">Editar</button>
                                @if($u->id !== auth()->id())
                                    <x-confirm-modal message="¿Está seguro de eliminar este usuario?" action="{{ route('users.destroy', $u) }}" method="DELETE">
                                        <button type="button" class="w-full text-white bg-red-700 hover:bg-red-800 rounded-lg text-sm px-4 py-2">Eliminar</button>
                                    </x-confirm-modal>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">{{ $users->links() }}</div>
            @endif
        </div>

        {{-- Create Modal --}}
        <div x-show="openCreate" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="openCreate = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Nuevo Usuario</h3>
                    <button @click="openCreate = false" class="p-1 rounded hover:bg-gray-100 text-gray-400"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" x-model="createName" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Correo Electrónico</label>
                        <div class="flex items-center">
                            <input type="text" name="email_local" x-model="createEmailLocal" required placeholder="nombre"
                                   class="flex-1 text-sm border-gray-300 rounded-l-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                            <span class="inline-flex items-center px-3 py-2 text-sm text-gray-500 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md font-medium">{{ '@' . $emailDomain }}</span>
                        </div>
                        @error('email_local')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" x-model="createRole" required
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                            <option value="administrador">Administrador</option>
                            <option value="usuario">Usuario</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openCreate = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm text-white bg-cyan-600 rounded-md hover:bg-cyan-700">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div x-show="openEditModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="openEditModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Usuario</h3>
                    <button @click="openEditModal = false" class="p-1 rounded hover:bg-gray-100 text-gray-400"><span class="material-symbols-outlined">close</span></button>
                </div>
                <form :action="'/users/' + editUserId" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" x-model="editName" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Correo Electrónico</label>
                        <div class="flex items-center">
                            <input type="text" name="email_local" x-model="editEmailLocal" required
                                   class="flex-1 text-sm border-gray-300 rounded-l-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                            <span class="inline-flex items-center px-3 py-2 text-sm text-gray-500 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md font-medium">{{ '@' . $emailDomain }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" x-model="editRole" required
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                            <option value="administrador">Administrador</option>
                            <option value="usuario">Usuario</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="password"
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-cyan-500">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openEditModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm text-white bg-cyan-600 rounded-md hover:bg-cyan-700">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('userManager', () => ({
                openCreate: {{ $errors->any() ? 'true' : 'false' }},
                createName: @js(old('name', '')),
                createEmailLocal: @js(old('email_local', '')),
                createRole: @js(old('role', 'usuario')),
                openEditModal: false,
                editUserId: null,
                editName: '',
                editEmailLocal: '',
                editRole: 'usuario',
                openEdit(id, name, emailLocal, role) {
                    this.editUserId = id;
                    this.editName = name;
                    this.editEmailLocal = emailLocal;
                    this.editRole = role;
                    this.openEditModal = true;
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
