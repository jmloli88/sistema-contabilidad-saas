<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Repase Médico
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Mensaje de error si ya existe un repase -->
            @if(session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-md">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-red-800">
                                No se puede crear el repase
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ session('error') }}</p>
                            </div>
                            @if(session('repase_existente_id'))
                                <div class="mt-4">
                                    <a href="{{ route('repases.show', session('repase_existente_id')) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver Repase Existente
                                    </a>
                                    <a href="{{ route('repases.edit', session('repase_existente_id')) }}" 
                                       class="ml-3 inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar Repase Existente
                                    </a>
                                </div>
                            @endif
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-red-400 hover:text-red-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('repases.store') }}" method="POST" 
                          x-data="repaseForm({{ json_encode($examenes) }}, {{ json_encode($preciosPorClinica) }})"
                          @submit.prevent="validateForm">
                        @csrf
                        
                        <!-- Token único para prevenir envíos duplicados -->
                        <input type="hidden" name="_submission_token" x-model="submissionToken">

                        <!-- Información Básica -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Información Básica
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                                <!-- Clínica -->
                                <div>
                                    <x-input-label for="clinica_id" value="Clínica *" />
                                    <select id="clinica_id" name="clinica_id" required
                                            @change="verificarDuplicado(); actualizarPreciosPorClinica($el.value)"
                                            class="mt-1 block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        <option value="">Seleccione una clínica</option>
                                        @foreach($clinicas as $clinica)
                                            <option value="{{ $clinica->id }}" {{ old('clinica_id') == $clinica->id ? 'selected' : '' }}>
                                                {{ $clinica->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('clinica_id')" class="mt-2" />
                                    
                                    <!-- Indicador de verificación de duplicados -->
                                    <div x-show="verificandoDuplicado" class="mt-2 text-sm text-gray-600 flex items-center">
                                        <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Verificando...
                                    </div>
                                    
                                    <!-- Alerta si ya existe un repase -->
                                    <div x-show="repaseDuplicadoEncontrado" class="mt-2 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                        <div class="flex items-start">
                                            <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-yellow-700 font-medium">
                                                    Ya existe un repase para esta fecha y clínica
                                                </p>
                                                <div class="mt-2 flex gap-2">
                                                    <a :href="repaseDuplicadoUrl" class="text-xs text-yellow-800 underline hover:text-yellow-900">Ver repase</a>
                                                    <a :href="repaseDuplicadoEditUrl" class="text-xs text-yellow-800 underline hover:text-yellow-900">Editar repase</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fecha -->
                                <div>
                                    <x-input-label for="fecha" value="Fecha *" />
                                    <x-text-input id="fecha" type="date" name="fecha" :value="old('fecha', date('Y-m-d'))" required 
                                                  @change="verificarDuplicado()"
                                                  class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                                </div>

                                <!-- Tipo de Precio -->
                                <div>
                                    <x-input-label for="tipo_precio" value="Tipo de Precio *" />
                                    <select id="tipo_precio" name="tipo_precio" required x-model="tipoPrecio" @change="recalcularExamenes"
                                            class="mt-1 block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        <option value="">Seleccione tipo</option>
                                        <option value="sin_nota" {{ old('tipo_precio') == 'sin_nota' ? 'selected' : '' }}>Sin Nota</option>
                                        <option value="con_nota" {{ old('tipo_precio') == 'con_nota' ? 'selected' : '' }}>Con Nota</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('tipo_precio')" class="mt-2" />
                                </div>

                                <!-- Estado -->
                                <div>
                                    <x-input-label for="estado" value="Estado *" />
                                    <select id="estado" name="estado" required
                                            class="mt-1 block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        <option value="pendiente" {{ old('estado', 'pendiente') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="pagado" {{ old('estado') == 'pagado' ? 'selected' : '' }}>Pagado</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                                </div>

                                <!-- Fecha de Pago -->
                                <div>
                                    <x-input-label for="fecha_pago" value="Fecha de Pago" />
                                    <x-text-input id="fecha_pago" type="date" name="fecha_pago" :value="old('fecha_pago')" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('fecha_pago')" class="mt-2" />
                                </div>

                                <!-- Cantidad de Consultas -->
                                <div>
                                    <x-input-label for="total_consultas" value="Cantidad de consultas *" />
                                    <x-text-input id="total_consultas" type="number" step="1" min="0" name="total_consultas" 
                                                  :value="old('total_consultas', '0')" required 
                                                  x-model.number="totalConsultas"
                                                  class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('total_consultas')" class="mt-2" />
                                </div>

                                <!-- Pedidos Doctor -->
                                <div>
                                    <x-input-label for="pedidos_doctor" value="Pedidos Doctor *" />
                                    <x-text-input id="pedidos_doctor" type="number" step="1" min="0" name="pedidos_doctor" 
                                                  :value="old('pedidos_doctor', '0')" required 
                                                  x-model.number="pedidosDoctor"
                                                  class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('pedidos_doctor')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="mt-4">
                                <x-input-label for="observaciones" value="Observaciones" />
                                <textarea id="observaciones" name="observaciones" rows="3" 
                                          class="border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 rounded-xl shadow-sm block mt-1 w-full">{{ old('observaciones') }}</textarea>
                                <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Exámenes -->
                        <div class="mb-6 border-t pt-6">
                            <div class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" @click="toggleSection('examenes')"
                                        class="w-full bg-green-100 hover:bg-green-200 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">EXÁMENES *</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.examenes}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.examenes" x-collapse class="bg-white p-4 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                                        <template x-for="(examen, index) in examenesDisponibles" :key="examen.id">
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="block text-sm font-medium text-gray-700" x-text="examen.nombre"></label>
                                                    <span class="text-xs text-gray-500" x-text="'R$' + getPrecioExamen(examen)"></span>
                                                </div>
                                                <div class="flex gap-2 items-center">
                                                    <div class="flex-1">
                                                        <input type="number" min="0" step="1" 
                                                               x-model.number="examenes[examen.id]" 
                                                               @input="calcularTotalExamenes"
                                                               :name="'examenes[' + examen.id + '][cantidad]'"
                                                               placeholder="Cantidad"
                                                               class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                                                        <input type="hidden" :name="'examenes[' + examen.id + '][examen_id]'" :value="examen.id">
                                                    </div>
                                                    <div class="text-right min-w-[80px]">
                                                        <span class="text-sm font-semibold text-gray-900" 
                                                              x-text="'R$' + calcularSubtotalExamen(examen.id).toFixed(2)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <x-input-error :messages="$errors->get('examenes')" class="mt-2" />
                        </div>

                        <!-- Gastos -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Gastos
                            </h3>

                            <!-- GASTOS OPERATIVOS -->
                            <div class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" @click="toggleSection('operativos')"
                                        class="w-full bg-blue-100 hover:bg-blue-200 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">GASTOS OPERATIVOS</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.operativos}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.operativos" x-collapse class="bg-white p-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                                        <textarea name="comentarios[operativos]" rows="2" 
                                                  class="border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 rounded-xl shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre gastos operativos...">{{ old('comentarios.operativos') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Honorarios Médicos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_medicos" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_medicos]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <input type="text" name="nombres_tecnicos[1]" x-model="nombresTecnicos[1]"
                                                   placeholder="Nombre Técnico Enfermero 1"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm mb-1">
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_tecnico_1" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_tecnico_1]"
                                                   placeholder="Monto"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <input type="text" name="nombres_tecnicos[2]" x-model="nombresTecnicos[2]"
                                                   placeholder="Nombre Técnico Enfermero 2"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm mb-1">
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_tecnico_2" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_tecnico_2]"
                                                   placeholder="Monto"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <template x-for="examen in examenesDisponibles" :key="'laudo_' + examen.id">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Honorarios Laudos ' + examen.nombre"></label>
                                                <input type="number" step="0.01" min="0" 
                                                       :value="gastos['honorarios_laudo_examen_' + examen.id] || 0"
                                                       @input="gastos['honorarios_laudo_examen_' + examen.id] = parseFloat($event.target.value) || 0; calcularTotalGastos()"
                                                       :name="'gastos[honorarios_laudo_examen_' + examen.id + ']'"
                                                       class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                            </div>
                                        </template>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Honorarios Motorista</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_motorista" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_motorista]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gasolina Equipo</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gasolina_equipo" 
                                                   @input="calcularTotalGastos" name="gastos[gasolina_equipo]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gasolina Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gasolina_medico" 
                                                   @input="calcularTotalGastos" name="gastos[gasolina_medico]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GASTOS ADMINISTRATIVOS -->
                            <div class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" @click="toggleSection('administrativos')"
                                        class="w-full bg-blue-100 hover:bg-blue-200 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">GASTOS ADMINISTRATIVOS</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.administrativos}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.administrativos" x-collapse class="bg-white p-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                                        <textarea name="comentarios[administrativos]" rows="2" 
                                                  class="border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 rounded-xl shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre gastos administrativos...">{{ old('comentarios.administrativos') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Software Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.software_medico" 
                                                   @input="calcularTotalGastos" name="gastos[software_medico]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alquiler Movilidad</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alquiler_movilidad" 
                                                   @input="calcularTotalGastos" name="gastos[alquiler_movilidad]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mantenimiento Equipos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.mantenimiento_equipos" 
                                                   @input="calcularTotalGastos" name="gastos[mantenimiento_equipos]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CAJA CHICA -->
                            <div class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" @click="toggleSection('cajaChica')"
                                        class="w-full bg-blue-100 hover:bg-blue-200 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">CAJA CHICA</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.cajaChica}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.cajaChica" x-collapse class="bg-white p-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                                        <textarea name="comentarios[caja_chica]" rows="2" 
                                                  class="border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 rounded-xl shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre caja chica...">{{ old('comentarios.caja_chica') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alimentación Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alimentacion_medico" 
                                                   @input="calcularTotalGastos" name="gastos[alimentacion_medico]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alimentación Personal</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alimentacion_personal" 
                                                   @input="calcularTotalGastos" name="gastos[alimentacion_personal]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Hospedajes</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.hospedajes" 
                                                   @input="calcularTotalGastos" name="gastos[hospedajes]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Estacionamiento</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.estacionamiento" 
                                                   @input="calcularTotalGastos" name="gastos[estacionamiento]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Papelería</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.papeleria" 
                                                   @input="calcularTotalGastos" name="gastos[papeleria]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pedagio Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.pedagio_medico" 
                                                   @input="calcularTotalGastos" name="gastos[pedagio_medico]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pedagios Personal</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.pedagios_personal" 
                                                   @input="calcularTotalGastos" name="gastos[pedagios_personal]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Otros</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.otros_caja_chica" 
                                                   @input="calcularTotalGastos" name="gastos[otros_caja_chica]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- INSUMIOS MÉDICOS -->
                            <div class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" @click="toggleSection('insumiosMedicos')"
                                        class="w-full bg-blue-100 hover:bg-blue-200 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">INSUMIOS MÉDICOS</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.insumiosMedicos}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.insumiosMedicos" x-collapse class="bg-white p-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                                        <textarea name="comentarios[insumios_medicos]" rows="2" 
                                                  class="border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 rounded-xl shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre insumos médicos...">{{ old('comentarios.insumios_medicos') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Electrodos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.electrodos" 
                                                   @input="calcularTotalGastos" name="gastos[electrodos]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Agujas Médicas</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.agujas_medicas" 
                                                   @input="calcularTotalGastos" name="gastos[agujas_medicas]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gel</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gel" 
                                                   @input="calcularTotalGastos" name="gastos[gel]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Guantes Latex</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.guantes_latex" 
                                                   @input="calcularTotalGastos" name="gastos[guantes_latex]"
                                                   class="block w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-input-error :messages="$errors->get('gastos')" class="mt-2" />
                        </div>

                        <!-- Resumen de Totales -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                Resumen de Totales
                            </h3>
                            
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 font-medium">Total Exámenes:</span>
                                        <span class="text-xl font-bold text-gray-900" x-text="'R$' + totalExamenes.toFixed(2)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 font-medium">Total Gastos:</span>
                                        <span class="text-xl font-bold text-red-600" x-text="'R$' + totalGastos.toFixed(2)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center border-t-2 border-blue-300 pt-2">
                                        <span class="text-gray-900 font-bold text-lg">Total Neto:</span>
                                        <span class="text-2xl font-bold" 
                                              :class="totalNeto >= 0 ? 'text-green-600' : 'text-red-600'"
                                              x-text="'R$' + totalNeto.toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end mt-6 space-x-4">
                            <a href="{{ route('repases.index') }}" class="text-gray-600 hover:text-gray-900 touch-manipulation min-h-[44px] inline-flex items-center">
                                Cancelar
                            </a>
                            <x-primary-button x-bind:disabled="isSubmitting" x-bind:class="{ 'opacity-50 cursor-not-allowed': isSubmitting }">
                                <span x-show="!isSubmitting">Guardar Repase</span>
                                <span x-show="isSubmitting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Guardando...
                                </span>
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function repaseForm(examenesData, preciosPorClinica = {}) {
            return {
                // Datos de exámenes disponibles - asegurar que los IDs sean números
                examenesDisponibles: examenesData.map(ex => ({
                    ...ex,
                    id: parseInt(ex.id),
                    precio_sin_nota: parseFloat(ex.precio_sin_nota),
                    precio_con_nota: parseFloat(ex.precio_con_nota),
                    // Guardar precios globales originales como fallback
                    precio_sin_nota_global: parseFloat(ex.precio_sin_nota),
                    precio_con_nota_global: parseFloat(ex.precio_con_nota)
                })),
                
                // Mapa de precios por clínica: { examenId: { clinicaId: { sin_nota, con_nota } } }
                preciosPorClinica: preciosPorClinica,
                
                // Estado del formulario
                tipoPrecio: '{{ old('tipo_precio', '') }}',
                totalConsultas: parseInt('{{ old('total_consultas', '0') }}'),
                pedidosDoctor: parseInt('{{ old('pedidos_doctor', '0') }}'),
                
                // Exámenes como objeto con ID de examen como clave y cantidad como valor
                examenes: {},
                
                // Gastos estructurados por categoría
                gastos: {
                    // Gastos Operativos
                    honorarios_medicos: parseFloat('{{ old('gastos.honorarios_medicos', '0') }}'),
                    honorarios_tecnico_1: parseFloat('{{ old('gastos.honorarios_tecnico_1', '0') }}'),
                    honorarios_tecnico_2: parseFloat('{{ old('gastos.honorarios_tecnico_2', '0') }}'),
                    // Los laudos dinámicos se inicializan en init()
                    honorarios_motorista: parseFloat('{{ old('gastos.honorarios_motorista', '0') }}'),
                    gasolina_equipo: parseFloat('{{ old('gastos.gasolina_equipo', '0') }}'),
                    gasolina_medico: parseFloat('{{ old('gastos.gasolina_medico', '0') }}'),
                    // Gastos Administrativos
                    software_medico: parseFloat('{{ old('gastos.software_medico', '0') }}'),
                    alquiler_movilidad: parseFloat('{{ old('gastos.alquiler_movilidad', '0') }}'),
                    mantenimiento_equipos: parseFloat('{{ old('gastos.mantenimiento_equipos', '0') }}'),
                    // Caja Chica
                    alimentacion_medico: parseFloat('{{ old('gastos.alimentacion_medico', '0') }}'),
                    alimentacion_personal: parseFloat('{{ old('gastos.alimentacion_personal', '0') }}'),
                    hospedajes: parseFloat('{{ old('gastos.hospedajes', '0') }}'),
                    estacionamiento: parseFloat('{{ old('gastos.estacionamiento', '0') }}'),
                    papeleria: parseFloat('{{ old('gastos.papeleria', '0') }}'),
                    pedagio_medico: parseFloat('{{ old('gastos.pedagio_medico', '0') }}'),
                    pedagios_personal: parseFloat('{{ old('gastos.pedagios_personal', '0') }}'),
                    otros_caja_chica: parseFloat('{{ old('gastos.otros_caja_chica', '0') }}'),
                    // Insumios Médicos
                    electrodos: parseFloat('{{ old('gastos.electrodos', '0') }}'),
                    agujas_medicas: parseFloat('{{ old('gastos.agujas_medicas', '0') }}'),
                    gel: parseFloat('{{ old('gastos.gel', '0') }}'),
                    guantes_latex: parseFloat('{{ old('gastos.guantes_latex', '0') }}')
                },
                
                // Nombres personalizados para técnicos enfermeros
                nombresTecnicos: {
                    1: '{{ old('nombres_tecnicos.1', '') }}',
                    2: '{{ old('nombres_tecnicos.2', '') }}'
                },
                
                // Control de secciones colapsables
                sectionsOpen: {
                    examenes: true,
                    operativos: true,
                    administrativos: false,
                    cajaChica: false,
                    insumiosMedicos: false
                },
                
                // Totales calculados
                totalExamenes: 0,
                totalGastos: 0,
                totalNeto: 0,
                
                // Control de envío del formulario
                isSubmitting: false,
                submissionToken: '',
                
                // Control de verificación de duplicados
                verificandoDuplicado: false,
                repaseDuplicadoEncontrado: false,
                repaseDuplicadoUrl: null,
                repaseDuplicadoEditUrl: null,
                
                init() {
                    // Generar token único para este formulario
                    this.submissionToken = this.generateUniqueToken();
                    
                    // Inicializar examenes con 0 para cada examen disponible
                    this.examenesDisponibles.forEach(examen => {
                        this.examenes[examen.id] = 0;
                    });
                    
                    // Inicializar gastos de laudos dinámicos con 0 para cada examen activo
                    this.examenesDisponibles.forEach(examen => {
                        this.gastos['honorarios_laudo_examen_' + examen.id] = 0;
                    });
                    
                    // Restaurar datos de old() si hay errores de validación
                    @if(old('examenes'))
                        @foreach(old('examenes') as $examenId => $examen)
                            @if(isset($examen['cantidad']))
                                this.examenes[{{ $examenId }}] = {{ $examen['cantidad'] }};
                            @endif
                        @endforeach
                    @endif
                    
                    @if(old('gastos'))
                        @foreach(old('gastos') as $key => $value)
                            @if(str_starts_with($key, 'honorarios_laudo'))
                                this.gastos['{{ $key }}'] = parseFloat({{ $value }});
                            @endif
                        @endforeach
                    @endif
                    
                    this.calcularTotalExamenes();
                    this.calcularTotalGastos();
                    this.calcularTotalNeto();
                },
                
                // Toggle sección colapsable
                toggleSection(section) {
                    this.sectionsOpen[section] = !this.sectionsOpen[section];
                },
                
                // Obtener precio según tipo_precio
                getPrecioExamen(examen) {
                    if (this.tipoPrecio === 'sin_nota') {
                        return parseFloat(examen.precio_sin_nota).toFixed(2);
                    } else if (this.tipoPrecio === 'con_nota') {
                        return parseFloat(examen.precio_con_nota).toFixed(2);
                    }
                    return '0.00';
                },
                
                // Calcular subtotal de un examen específico por ID
                calcularSubtotalExamen(examenId) {
                    const cantidad = this.examenes[examenId] || 0;
                    const examenData = this.examenesDisponibles.find(e => e.id == examenId);
                    
                    if (examenData && cantidad > 0 && this.tipoPrecio) {
                        const precio = this.tipoPrecio === 'sin_nota' 
                            ? parseFloat(examenData.precio_sin_nota) 
                            : parseFloat(examenData.precio_con_nota);
                        
                        return precio * cantidad;
                    }
                    
                    return 0;
                },
                
                // Calcular total de exámenes
                calcularTotalExamenes() {
                    this.totalExamenes = 0;
                    
                    Object.keys(this.examenes).forEach(examenId => {
                        this.totalExamenes += this.calcularSubtotalExamen(examenId);
                    });
                    
                    this.calcularTotalNeto();
                },
                
                // Recalcular todos los exámenes cuando cambia tipo_precio
                recalcularExamenes() {
                    this.calcularTotalExamenes();
                },
                
                // Actualizar precios de exámenes según la clínica seleccionada
                actualizarPreciosPorClinica(clinicaId) {
                    clinicaId = parseInt(clinicaId);
                    this.examenesDisponibles.forEach(examen => {
                        const override = this.preciosPorClinica[examen.id]?.[clinicaId];
                        if (override) {
                            if (override.sin_nota !== null) {
                                examen.precio_sin_nota = override.sin_nota;
                            } else {
                                examen.precio_sin_nota = examen.precio_sin_nota_global;
                            }
                            if (override.con_nota !== null) {
                                examen.precio_con_nota = override.con_nota;
                            } else {
                                examen.precio_con_nota = examen.precio_con_nota_global;
                            }
                        } else {
                            // Sin override: usar precios globales
                            examen.precio_sin_nota = examen.precio_sin_nota_global;
                            examen.precio_con_nota = examen.precio_con_nota_global;
                        }
                    });
                    this.recalcularExamenes();
                },
                
                // Calcular total de gastos
                calcularTotalGastos() {
                    this.totalGastos = Object.values(this.gastos).reduce((sum, valor) => {
                        return sum + (parseFloat(valor) || 0);
                    }, 0);
                    
                    this.calcularTotalNeto();
                },
                
                // Calcular total neto
                calcularTotalNeto() {
                    this.totalNeto = this.totalExamenes - this.totalGastos;
                },
                
                // Generar token único para prevenir envíos duplicados
                generateUniqueToken() {
                    return Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
                },
                
                // Verificar si ya existe un repase con la misma fecha y clínica
                async verificarDuplicado() {
                    const clinicaId = document.getElementById('clinica_id').value;
                    const fecha = document.getElementById('fecha').value;
                    
                    if (!clinicaId || !fecha) {
                        return false;
                    }
                    
                    this.verificandoDuplicado = true;
                    
                    try {
                        const response = await fetch(`/api/repases/verificar-duplicado?clinica_id=${clinicaId}&fecha=${fecha}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        this.repaseDuplicadoEncontrado = data.existe;
                        this.repaseDuplicadoUrl = data.repase_url;
                        this.repaseDuplicadoEditUrl = data.repase_edit_url;
                        
                        return data.existe;
                    } catch (error) {
                        console.error('Error al verificar duplicado:', error);
                        return false;
                    } finally {
                        this.verificandoDuplicado = false;
                    }
                },
                
                // Validar formulario antes de enviar
                async validateForm() {
                    // Prevenir doble envío
                    if (this.isSubmitting) {
                        console.log('Formulario ya está siendo enviado, ignorando...');
                        return;
                    }
                    
                    // Validar que tipo_precio esté seleccionado
                    if (!this.tipoPrecio) {
                        alert('Debe seleccionar un tipo de precio.');
                        return;
                    }
                    
                    // Validar que haya al menos un examen con cantidad > 0
                    const tieneExamenes = Object.values(this.examenes).some(cantidad => cantidad > 0);
                    if (!tieneExamenes) {
                        alert('Debe agregar al menos un examen con cantidad mayor a 0.');
                        return;
                    }
                    
                    // Verificar si ya existe un repase con la misma fecha y clínica
                    const existeDuplicado = await this.verificarDuplicado();
                    
                    if (existeDuplicado) {
                        const mensaje = 'Ya existe un repase para esta clínica y fecha.\n\n' +
                                      '¿Desea ver o editar el repase existente?\n\n' +
                                      'Haga clic en "Aceptar" para ver el repase existente, o "Cancelar" para quedarse en esta página.';
                        
                        if (confirm(mensaje)) {
                            window.location.href = this.repaseDuplicadoUrl;
                        }
                        return;
                    }
                    
                    // Marcar como enviando
                    this.isSubmitting = true;
                    
                    // Si todo está bien, enviar el formulario
                    this.$el.submit();
                }
            }
        }
    </script>
</x-app-layout>
