<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Repase Médico
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('repases.update', $repase) }}" method="POST" 
                          x-data="repaseForm({{ json_encode($examenes) }}, {{ json_encode($repase) }}, {{ json_encode($preciosPorClinica) }})"
                          @submit.prevent="validateForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Token único para prevenir envíos duplicados -->
                        <input type="hidden" name="_submission_token" x-model="submissionToken">

                        <!-- Información Básica -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Información Básica</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Clínica -->
                                <div>
                                    <x-input-label for="clinica_id" value="Clínica *" />
                                    <select id="clinica_id" name="clinica_id" required
                                            @change="actualizarPreciosPorClinica($el.value)"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Seleccione una clínica</option>
                                        @foreach($clinicas as $clinica)
                                            <option value="{{ $clinica->id }}" {{ old('clinica_id', $repase->clinica_id) == $clinica->id ? 'selected' : '' }}>
                                                {{ $clinica->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('clinica_id')" class="mt-2" />
                                </div>

                                <!-- Fecha -->
                                <div>
                                    <x-input-label for="fecha" value="Fecha *" />
                                    <x-text-input id="fecha" type="date" name="fecha" :value="old('fecha', $repase->fecha->format('Y-m-d'))" required class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                                </div>

                                <!-- Tipo de Precio -->
                                <div>
                                    <x-input-label for="tipo_precio" value="Tipo de Precio *" />
                                    <select id="tipo_precio" name="tipo_precio" required x-model="tipoPrecio" @change="recalcularExamenes"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Seleccione tipo</option>
                                        <option value="sin_nota" {{ old('tipo_precio', $repase->tipo_precio) == 'sin_nota' ? 'selected' : '' }}>Sin Nota</option>
                                        <option value="con_nota" {{ old('tipo_precio', $repase->tipo_precio) == 'con_nota' ? 'selected' : '' }}>Con Nota</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('tipo_precio')" class="mt-2" />
                                </div>

                                <!-- Estado -->
                                <div>
                                    <x-input-label for="estado" value="Estado *" />
                                    <select id="estado" name="estado" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="pendiente" {{ old('estado', $repase->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="pagado" {{ old('estado', $repase->estado) == 'pagado' ? 'selected' : '' }}>Pagado</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                                </div>

                                <!-- Fecha de Pago -->
                                <div>
                                    <x-input-label for="fecha_pago" value="Fecha de Pago" />
                                    <x-text-input id="fecha_pago" type="date" name="fecha_pago" :value="old('fecha_pago', $repase->fecha_pago?->format('Y-m-d'))" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('fecha_pago')" class="mt-2" />
                                </div>

                                <!-- Cantidad de Consultas -->
                                <div>
                                    <x-input-label for="total_consultas" value="Cantidad de consultas *" />
                                    <x-text-input id="total_consultas" type="number" step="1" min="0" name="total_consultas" 
                                                  :value="old('total_consultas', $repase->total_consultas)" required 
                                                  x-model.number="totalConsultas"
                                                  class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('total_consultas')" class="mt-2" />
                                </div>

                                <!-- Pedidos Doctor -->
                                <div>
                                    <x-input-label for="pedidos_doctor" value="Pedidos Doctor *" />
                                    <x-text-input id="pedidos_doctor" type="number" step="1" min="0" name="pedidos_doctor" 
                                                  :value="old('pedidos_doctor', $repase->pedidos_doctor ?? 0)" required 
                                                  x-model.number="pedidosDoctor"
                                                  class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('pedidos_doctor')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="mt-4">
                                <x-input-label for="observaciones" value="Observaciones" />
                                <textarea id="observaciones" name="observaciones" rows="3" 
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">{{ old('observaciones', $repase->observaciones) }}</textarea>
                                <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Exámenes -->
                        <div class="mb-6 border-t pt-6">
                            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">
                                <button type="button" @click="toggleSection('examenes')"
                                        class="w-full bg-green-200 hover:bg-green-300 px-4 py-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">EXÁMENES *</span>
                                    <svg class="w-5 h-5 transform transition-transform" :class="{'rotate-180': sectionsOpen.examenes}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="sectionsOpen.examenes" x-collapse class="bg-white p-4 space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
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
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Gastos</h3>

                            <!-- GASTOS OPERATIVOS -->
                            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">
                                <button type="button" @click="toggleSection('operativos')"
                                        class="w-full bg-blue-200 hover:bg-blue-300 px-4 py-3 flex justify-between items-center">
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
                                                  class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre gastos operativos...">{{ old('comentarios.operativos', $repase->comentarios_operativos ?? '') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Honorarios Médicos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_medicos" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_medicos]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <input type="text" name="nombres_tecnicos[1]" x-model="nombresTecnicos[1]"
                                                   placeholder="Nombre Técnico Enfermero 1"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm mb-1">
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_tecnico_1" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_tecnico_1]"
                                                   placeholder="Monto"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <input type="text" name="nombres_tecnicos[2]" x-model="nombresTecnicos[2]"
                                                   placeholder="Nombre Técnico Enfermero 2"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm mb-1">
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_tecnico_2" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_tecnico_2]"
                                                   placeholder="Monto"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <template x-for="examen in examenesDisponibles" :key="'laudo_' + examen.id">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Honorarios Laudos ' + examen.nombre"></label>
                                                <input type="number" step="0.01" min="0" 
                                                       :value="gastos['honorarios_laudo_examen_' + examen.id] || 0"
                                                       @input="gastos['honorarios_laudo_examen_' + examen.id] = parseFloat($event.target.value) || 0; calcularTotalGastos()"
                                                       :name="'gastos[honorarios_laudo_examen_' + examen.id + ']'"
                                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </template>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Honorarios Motorista</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.honorarios_motorista" 
                                                   @input="calcularTotalGastos" name="gastos[honorarios_motorista]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gasolina Equipo</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gasolina_equipo" 
                                                   @input="calcularTotalGastos" name="gastos[gasolina_equipo]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gasolina Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gasolina_medico" 
                                                   @input="calcularTotalGastos" name="gastos[gasolina_medico]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GASTOS ADMINISTRATIVOS -->
                            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">
                                <button type="button" @click="toggleSection('administrativos')"
                                        class="w-full bg-blue-200 hover:bg-blue-300 px-4 py-3 flex justify-between items-center">
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
                                                  class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre gastos administrativos...">{{ old('comentarios.administrativos', $repase->comentarios_administrativos ?? '') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Software Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.software_medico" 
                                                   @input="calcularTotalGastos" name="gastos[software_medico]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alquiler Movilidad</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alquiler_movilidad" 
                                                   @input="calcularTotalGastos" name="gastos[alquiler_movilidad]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mantenimiento Equipos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.mantenimiento_equipos" 
                                                   @input="calcularTotalGastos" name="gastos[mantenimiento_equipos]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CAJA CHICA -->
                            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">
                                <button type="button" @click="toggleSection('cajaChica')"
                                        class="w-full bg-blue-200 hover:bg-blue-300 px-4 py-3 flex justify-between items-center">
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
                                                  class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre caja chica...">{{ old('comentarios.caja_chica', $repase->comentarios_caja_chica ?? '') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alimentación Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alimentacion_medico" 
                                                   @input="calcularTotalGastos" name="gastos[alimentacion_medico]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alimentación Personal</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.alimentacion_personal" 
                                                   @input="calcularTotalGastos" name="gastos[alimentacion_personal]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Hospedajes</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.hospedajes" 
                                                   @input="calcularTotalGastos" name="gastos[hospedajes]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Estacionamiento</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.estacionamiento" 
                                                   @input="calcularTotalGastos" name="gastos[estacionamiento]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Papelería</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.papeleria" 
                                                   @input="calcularTotalGastos" name="gastos[papeleria]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pedagio Médico</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.pedagio_medico" 
                                                   @input="calcularTotalGastos" name="gastos[pedagio_medico]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pedagios Personal</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.pedagios_personal" 
                                                   @input="calcularTotalGastos" name="gastos[pedagios_personal]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Otros</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.otros_caja_chica" 
                                                   @input="calcularTotalGastos" name="gastos[otros_caja_chica]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- INSUMIOS MÉDICOS -->
                            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">
                                <button type="button" @click="toggleSection('insumiosMedicos')"
                                        class="w-full bg-blue-200 hover:bg-blue-300 px-4 py-3 flex justify-between items-center">
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
                                                  class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm"
                                                  placeholder="Comentarios adicionales sobre insumos médicos...">{{ old('comentarios.insumios_medicos', $repase->comentarios_insumios_medicos ?? '') }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Electrodos</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.electrodos" 
                                                   @input="calcularTotalGastos" name="gastos[electrodos]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Agujas Médicas</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.agujas_medicas" 
                                                   @input="calcularTotalGastos" name="gastos[agujas_medicas]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Gel</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.gel" 
                                                   @input="calcularTotalGastos" name="gastos[gel]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Guantes Latex</label>
                                            <input type="number" step="0.01" min="0" x-model.number="gastos.guantes_latex" 
                                                   @input="calcularTotalGastos" name="gastos[guantes_latex]"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-input-error :messages="$errors->get('gastos')" class="mt-2" />
                        </div>

                        <!-- Resumen de Totales -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Resumen de Totales</h3>
                            
                            <div class="bg-blue-50 p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <a href="{{ route('repases.show', $repase) }}" class="text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                            <x-primary-button x-bind:disabled="isSubmitting">
                                <span x-show="!isSubmitting">Actualizar Repase</span>
                                <span x-show="isSubmitting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Actualizando...
                                </span>
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function repaseForm(examenesData, repaseData, preciosPorClinica = {}) {
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
                tipoPrecio: '{{ old('tipo_precio', $repase->tipo_precio) }}',
                totalConsultas: parseInt('{{ old('total_consultas', $repase->total_consultas) }}'),
                pedidosDoctor: parseInt('{{ old('pedidos_doctor', $repase->pedidos_doctor ?? 0) }}'),
                
                // Exámenes como objeto con ID de examen como clave y cantidad como valor
                examenes: {},
                
                // Gastos estructurados por categoría
                gastos: {
                    // Gastos Operativos
                    honorarios_medicos: 0,
                    honorarios_tecnico_1: 0,
                    honorarios_tecnico_2: 0,
                    // Los laudos dinámicos se inicializan en init()
                    honorarios_motorista: 0,
                    gasolina_equipo: 0,
                    gasolina_medico: 0,
                    // Gastos Administrativos
                    software_medico: 0,
                    alquiler_movilidad: 0,
                    mantenimiento_equipos: 0,
                    // Caja Chica
                    alimentacion_medico: 0,
                    alimentacion_personal: 0,
                    hospedajes: 0,
                    estacionamiento: 0,
                    papeleria: 0,
                    pedagio_medico: 0,
                    pedagios_personal: 0,
                    otros_caja_chica: 0,
                    // Insumios Médicos
                    electrodos: 0,
                    agujas_medicas: 0,
                    gel: 0,
                    guantes_latex: 0
                },
                
                // Nombres personalizados para técnicos enfermeros
                nombresTecnicos: {
                    1: '',
                    2: ''
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
                
                init() {
                    // Generar token único para este formulario
                    this.submissionToken = this.generateUniqueToken();
                    
                    // Inicializar examenes con 0 para cada examen disponible
                    this.examenesDisponibles.forEach(examen => {
                        this.examenes[examen.id] = 0;
                    });
                    
                    // Cargar exámenes existentes del repase
                    @foreach($repase->repaseExamenes as $repaseExamen)
                        this.examenes[{{ $repaseExamen->examen_id }}] = {{ $repaseExamen->cantidad }};
                    @endforeach
                    
                    // Cargar gastos existentes del repase y mapearlos a las categorías
                    // IMPORTANTE: Usar = en lugar de += para evitar sumar gastos duplicados
                    @foreach($repase->gastos as $gasto)
                        @php
                            $descripcion = strtolower($gasto->descripcion ?? '');
                            $gastoKey = $gasto->gasto_key ?? null;
                            
                            // Si no tiene gasto_key, usar mapeo por descripción (compatibilidad con datos antiguos)
                            if (!$gastoKey) {
                                // Mapear descripción a clave de gasto
                                if (str_contains($descripcion, 'honorarios médicos') || str_contains($descripcion, 'honorarios doctor')) $gastoKey = 'honorarios_medicos';
                                // Manejar descripciones antiguas de técnicos
                                elseif (str_contains($descripcion, 'técnico enfermero 1') || str_contains($descripcion, 'tecnico enfermero 1')) $gastoKey = 'honorarios_tecnico_1';
                                elseif (str_contains($descripcion, 'técnico enfermero 2') || str_contains($descripcion, 'tecnico enfermero 2')) $gastoKey = 'honorarios_tecnico_2';
                                // Si solo dice "técnicos" sin especificar, asignar a técnico 1
                                elseif ($descripcion === 'honorarios técnicos' || $descripcion === 'honorarios tecnicos') $gastoKey = 'honorarios_tecnico_1';
                                elseif (str_contains($descripcion, 'laudos egg')) $gastoKey = 'honorarios_laudos_egg';
                                elseif (str_contains($descripcion, 'laudos potencial')) $gastoKey = 'honorarios_laudos_potencial';
                                elseif (str_contains($descripcion, 'electromiografía')) $gastoKey = 'honorarios_laudo_electromiografia';
                                elseif (str_contains($descripcion, 'motorista')) $gastoKey = 'honorarios_motorista';
                                elseif (str_contains($descripcion, 'gasolina equipo')) $gastoKey = 'gasolina_equipo';
                                elseif (str_contains($descripcion, 'gasolina médico')) $gastoKey = 'gasolina_medico';
                                elseif (str_contains($descripcion, 'software')) $gastoKey = 'software_medico';
                                elseif (str_contains($descripcion, 'alquiler')) $gastoKey = 'alquiler_movilidad';
                                elseif (str_contains($descripcion, 'mantenimiento')) $gastoKey = 'mantenimiento_equipos';
                                elseif (str_contains($descripcion, 'alimentación médico')) $gastoKey = 'alimentacion_medico';
                                elseif (str_contains($descripcion, 'alimentación personal')) $gastoKey = 'alimentacion_personal';
                                elseif (str_contains($descripcion, 'hospedaje')) $gastoKey = 'hospedajes';
                                elseif (str_contains($descripcion, 'estacionamiento')) $gastoKey = 'estacionamiento';
                                elseif (str_contains($descripcion, 'papelería')) $gastoKey = 'papeleria';
                                elseif (str_contains($descripcion, 'pedagio médico')) $gastoKey = 'pedagio_medico';
                                elseif (str_contains($descripcion, 'pedagios personal')) $gastoKey = 'pedagios_personal';
                                elseif (str_contains($descripcion, 'otros')) $gastoKey = 'otros_caja_chica';
                                elseif (str_contains($descripcion, 'electrodo')) $gastoKey = 'electrodos';
                                elseif (str_contains($descripcion, 'aguja')) $gastoKey = 'agujas_medicas';
                                elseif (str_contains($descripcion, 'gel')) $gastoKey = 'gel';
                                elseif (str_contains($descripcion, 'guante')) $gastoKey = 'guantes_latex';
                            }
                            
                            // Detectar si es un nombre personalizado (no es la descripción por defecto)
                            $esNombrePersonalizado = false;
                            if ($gastoKey === 'honorarios_tecnico_1' && $gasto->descripcion !== 'Honorarios Técnico Enfermero 1') {
                                $esNombrePersonalizado = true;
                            } elseif ($gastoKey === 'honorarios_tecnico_2' && $gasto->descripcion !== 'Honorarios Técnico Enfermero 2') {
                                $esNombrePersonalizado = true;
                            }
                        @endphp
                        
                        @if($gastoKey)
                            this.gastos.{{ $gastoKey }} = parseFloat({{ $gasto->monto }});
                        @endif
                        
                        @if($esNombrePersonalizado)
                            @if($gastoKey === 'honorarios_tecnico_1')
                                this.nombresTecnicos[1] = '{{ addslashes($gasto->descripcion) }}';
                            @elseif($gastoKey === 'honorarios_tecnico_2')
                                this.nombresTecnicos[2] = '{{ addslashes($gasto->descripcion) }}';
                            @endif
                        @endif
                    @endforeach
                    
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
                            this.gastos.{{ $key }} = parseFloat({{ $value }});
                        @endforeach
                    @endif
                    
                    // Inicializar gastos de laudos dinámicos para exámenes activos sin valor guardado
                    this.examenesDisponibles.forEach(examen => {
                        const key = 'honorarios_laudo_examen_' + examen.id;
                        if (typeof this.gastos[key] === 'undefined') {
                            this.gastos[key] = 0;
                        }
                    });
                    
                    // Aplicar precios por clínica del repase actual
                    this.actualizarPreciosPorClinica('{{ $repase->clinica_id }}');
                    
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
                
                // Validar formulario antes de enviar
                validateForm() {
                    // Prevenir doble envío
                    if (this.isSubmitting) {
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
                    
                    // Marcar como enviando y enviar el formulario
                    this.isSubmitting = true;
                    this.$el.submit();
                }
            }
        }
    </script>
</x-app-layout>
