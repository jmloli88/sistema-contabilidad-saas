<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle del Repase
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Botones de acción (ahora dentro del contenido para que se desplacen) -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-2">
                        <button id="btn-generar-imagen" type="button" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl inline-flex items-center justify-center text-sm sm:text-base">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Descargar Imagen
                        </button>
                        <a href="{{ route('repases.edit', $repase) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl text-center text-sm sm:text-base">
                            Editar
                        </a>
                        <a href="{{ route('repases.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-xl text-center text-sm sm:text-base">
                            Volver al Listado
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contenido para exportar como imagen (oculto visualmente pero renderizado) -->
            <div id="repase-image-content" data-clinica="{{ Str::slug($repase->clinica->nombre) }}" class="bg-white p-8 rounded-lg shadow-lg" style="position: fixed; left: -9999px; top: 0; width: 800px; z-index: -1;">
                <!-- Encabezado de la imagen -->
                <div class="text-center mb-6 border-b-2 border-gray-300 pb-4">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">REPASE MÉDICO</h1>
                    <p class="text-lg font-semibold text-gray-700">{{ $repase->clinica->nombre }}</p>
                    <p class="text-sm text-gray-600">Fecha: {{ $repase->fecha->format('d/m/Y') }}</p>
                </div>

                <!-- Información Básica -->
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-3 bg-blue-100 px-3 py-2 rounded">Información General</h2>
                    <div class="grid grid-cols-2 gap-4 px-3">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Tipo de Precio:</p>
                            <p class="text-base font-semibold text-gray-900">
                                {{ $repase->tipo_precio === 'sin_nota' ? 'Sin Nota' : 'Con Nota' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Exámenes Realizados -->
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-3 bg-green-100 px-3 py-2 rounded">Exámenes Realizados</h2>
                    @if($repase->repaseExamenes->isNotEmpty())
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Examen</th>
                                    <th class="px-3 py-2 text-center font-semibold text-gray-700">Cant.</th>
                                    <th class="px-3 py-2 text-right font-semibold text-gray-700">Precio Unit.</th>
                                    <th class="px-3 py-2 text-right font-semibold text-gray-700">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($repase->repaseExamenes as $repaseExamen)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-900">{{ $repaseExamen->examen->nombre }}</td>
                                        <td class="px-3 py-2 text-center text-gray-900">{{ $repaseExamen->cantidad }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">R${{ number_format($repaseExamen->precio_unitario_usado, 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-900">R${{ number_format($repaseExamen->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- Resumen Financiero -->
                <div class="border-t-2 border-gray-300 pt-4">
                    <div class="bg-blue-50 p-4 rounded-lg space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-semibold text-gray-700">Total Exámenes:</span>
                            <span class="text-lg font-bold text-gray-900">R${{ number_format($repase->total_examenes, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t-2 border-blue-300 pt-2 mt-2">
                            <span class="text-xl font-bold text-gray-900">TOTAL:</span>
                            <span class="text-2xl font-bold text-green-600">R${{ number_format($repase->total_examenes, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pie de página -->
                <div class="mt-6 text-center text-xs text-gray-500 border-t pt-3">
                    <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Información General -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información General</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Clínica</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repase->clinica->nombre }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repase->fecha->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado</label>
                            <p class="mt-1">
                                @if($repase->estado === 'pendiente')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Pendiente
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Pagado
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de Pago</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $repase->fecha_pago ? $repase->fecha_pago->format('d/m/Y') : '-' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Precio</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $repase->tipo_precio === 'sin_nota' ? 'Sin Nota' : 'Con Nota' }}
                            </p>
                        </div>
                    </div>

                    @if($repase->observaciones)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repase->observaciones }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Exámenes Realizados -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Exámenes Realizados</h3>
                    @if($repase->repaseExamenes->isEmpty())
                        <p class="text-gray-500 text-center py-4">No hay exámenes registrados.</p>
                    @else
                        <!-- Vista móvil: Tarjetas -->
                        <div class="block sm:hidden space-y-3">
                            @foreach($repase->repaseExamenes as $repaseExamen)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="font-medium text-gray-900 mb-3">
                                        {{ $repaseExamen->examen->nombre }}
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="text-gray-600">Cantidad:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $repaseExamen->cantidad }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-gray-600">Precio Unit.:</span>
                                            <span class="font-semibold text-gray-900 ml-1">R${{ number_format($repaseExamen->precio_unitario_usado, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-gray-300 flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-base font-bold text-gray-900">R${{ number_format($repaseExamen->subtotal, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                            <!-- Total en vista móvil -->
                            <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-bold text-gray-900">Total Exámenes:</span>
                                    <span class="text-lg font-bold text-blue-600">R${{ number_format($repase->total_examenes, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Vista desktop: Tabla -->
                        <div class="hidden sm:block overflow-x-auto rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Examen
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cantidad
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Precio Unitario
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($repase->repaseExamenes as $repaseExamen)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $repaseExamen->examen->nombre }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center text-gray-900">
                                                {{ $repaseExamen->cantidad }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right text-gray-900">
                                                R${{ number_format($repaseExamen->precio_unitario_usado, 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">
                                                R${{ number_format($repaseExamen->subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-6 py-4 text-sm font-semibold text-right text-gray-900">
                                            Total Exámenes:
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-right text-gray-900">
                                            R${{ number_format($repase->total_examenes, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Consultas -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Adicional</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">Cantidad de Consultas:</span>
                            <span class="text-lg font-bold text-gray-900">{{ $repase->total_consultas }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">Pedidos Doctor:</span>
                            <span class="text-lg font-bold text-gray-900">{{ $repase->pedidos_doctor ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gastos -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Gastos Detallados</h3>
                    @if($repase->gastos->isEmpty())
                        <p class="text-gray-500 text-center py-4">No hay gastos registrados.</p>
                    @else
                        @php
                            // Agrupar gastos por categoría basándose en la descripción
                            // Cada gasto se mostrará individualmente con su descripción completa
                            $gastosOperativos = $repase->gastos->filter(function($g) {
                                return in_array($g->tipo, ['doctor', 'tecnico', 'laudos', 'gasolina']) && 
                                       (str_contains($g->descripcion, 'Honorarios') || str_contains($g->descripcion, 'Gasolina') || $g->tipo === 'tecnico');
                            })->sortBy('descripcion');
                            
                            $gastosAdministrativos = $repase->gastos->filter(function($g) {
                                return str_contains($g->descripcion, 'Software') || 
                                       str_contains($g->descripcion, 'Alquiler') || 
                                       str_contains($g->descripcion, 'Mantenimiento');
                            });
                            
                            $cajaChica = $repase->gastos->filter(function($g) {
                                return str_contains($g->descripcion, 'Alimentación') || 
                                       str_contains($g->descripcion, 'Hospedaje') || 
                                       str_contains($g->descripcion, 'Estacionamiento') || 
                                       str_contains($g->descripcion, 'Papelería') || 
                                       str_contains($g->descripcion, 'Pedagio') ||
                                       str_contains($g->descripcion, 'Otros');
                            });
                            
                            $insumiosMedicos = $repase->gastos->filter(function($g) {
                                return str_contains($g->descripcion, 'Electrodo') || 
                                       str_contains($g->descripcion, 'Aguja') || 
                                       str_contains($g->descripcion, 'Gel') || 
                                       str_contains($g->descripcion, 'Guante');
                            });
                            
                            $otrosGastos = $repase->gastos->reject(function($g) use ($gastosOperativos, $gastosAdministrativos, $cajaChica, $insumiosMedicos) {
                                return $gastosOperativos->contains($g) || 
                                       $gastosAdministrativos->contains($g) || 
                                       $cajaChica->contains($g) || 
                                       $insumiosMedicos->contains($g);
                            });
                        @endphp

                        <div class="space-y-4">
                            <!-- Gastos Operativos -->
                            @if($gastosOperativos->isNotEmpty() || $repase->comentarios_operativos)
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="bg-blue-100 px-4 py-3">
                                        <h4 class="font-semibold text-gray-800">Gastos Operativos</h4>
                                    </div>
                                    <div class="p-4">
                                        @if($repase->comentarios_operativos)
                                            <div class="mb-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                                <p class="text-xs font-medium text-gray-600 mb-1">Comentarios:</p>
                                                <p class="text-sm text-gray-700">{{ $repase->comentarios_operativos }}</p>
                                            </div>
                                        @endif
                                        @if($gastosOperativos->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($gastosOperativos as $gasto)
                                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded border border-gray-200">
                                                        <div class="flex-1">
                                                            <span class="text-sm font-medium text-gray-900">{{ $gasto->descripcion }}</span>
                                                            <span class="text-xs text-gray-500 ml-2">({{ ucfirst($gasto->tipo) }})</span>
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-900 ml-4">R${{ number_format($gasto->monto, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                <span class="text-sm font-semibold text-gray-700">Subtotal Operativos:</span>
                                                <span class="text-base font-bold text-blue-600">R${{ number_format($gastosOperativos->sum('monto'), 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Gastos Administrativos -->
                            @if($gastosAdministrativos->isNotEmpty() || $repase->comentarios_administrativos)
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="bg-purple-100 px-4 py-3">
                                        <h4 class="font-semibold text-gray-800">Gastos Administrativos</h4>
                                    </div>
                                    <div class="p-4">
                                        @if($repase->comentarios_administrativos)
                                            <div class="mb-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                                                <p class="text-xs font-medium text-gray-600 mb-1">Comentarios:</p>
                                                <p class="text-sm text-gray-700">{{ $repase->comentarios_administrativos }}</p>
                                            </div>
                                        @endif
                                        @if($gastosAdministrativos->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($gastosAdministrativos as $gasto)
                                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded border border-gray-200">
                                                        <div class="flex-1">
                                                            <span class="text-sm font-medium text-gray-900">{{ $gasto->descripcion }}</span>
                                                            <span class="text-xs text-gray-500 ml-2">({{ ucfirst($gasto->tipo) }})</span>
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-900 ml-4">R${{ number_format($gasto->monto, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                <span class="text-sm font-semibold text-gray-700">Subtotal Administrativos:</span>
                                                <span class="text-base font-bold text-purple-600">R${{ number_format($gastosAdministrativos->sum('monto'), 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Caja Chica -->
                            @if($cajaChica->isNotEmpty() || $repase->comentarios_caja_chica)
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="bg-yellow-100 px-4 py-3">
                                        <h4 class="font-semibold text-gray-800">Caja Chica</h4>
                                    </div>
                                    <div class="p-4">
                                        @if($repase->comentarios_caja_chica)
                                            <div class="mb-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                                <p class="text-xs font-medium text-gray-600 mb-1">Comentarios:</p>
                                                <p class="text-sm text-gray-700">{{ $repase->comentarios_caja_chica }}</p>
                                            </div>
                                        @endif
                                        @if($cajaChica->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($cajaChica as $gasto)
                                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded border border-gray-200">
                                                        <div class="flex-1">
                                                            <span class="text-sm font-medium text-gray-900">{{ $gasto->descripcion }}</span>
                                                            <span class="text-xs text-gray-500 ml-2">({{ ucfirst($gasto->tipo) }})</span>
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-900 ml-4">R${{ number_format($gasto->monto, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                <span class="text-sm font-semibold text-gray-700">Subtotal Caja Chica:</span>
                                                <span class="text-base font-bold text-yellow-600">R${{ number_format($cajaChica->sum('monto'), 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Insumios Médicos -->
                            @if($insumiosMedicos->isNotEmpty() || $repase->comentarios_insumios_medicos)
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="bg-green-100 px-4 py-3">
                                        <h4 class="font-semibold text-gray-800">Insumios Médicos</h4>
                                    </div>
                                    <div class="p-4">
                                        @if($repase->comentarios_insumios_medicos)
                                            <div class="mb-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                                <p class="text-xs font-medium text-gray-600 mb-1">Comentarios:</p>
                                                <p class="text-sm text-gray-700">{{ $repase->comentarios_insumios_medicos }}</p>
                                            </div>
                                        @endif
                                        @if($insumiosMedicos->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($insumiosMedicos as $gasto)
                                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded border border-gray-200">
                                                        <div class="flex-1">
                                                            <span class="text-sm font-medium text-gray-900">{{ $gasto->descripcion }}</span>
                                                            <span class="text-xs text-gray-500 ml-2">({{ ucfirst($gasto->tipo) }})</span>
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-900 ml-4">R${{ number_format($gasto->monto, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                <span class="text-sm font-semibold text-gray-700">Subtotal Insumios:</span>
                                                <span class="text-base font-bold text-green-600">R${{ number_format($insumiosMedicos->sum('monto'), 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Otros Gastos -->
                            @if($otrosGastos->isNotEmpty())
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="bg-gray-100 px-4 py-3">
                                        <h4 class="font-semibold text-gray-800">Otros Gastos</h4>
                                    </div>
                                    <div class="p-4">
                                        <div class="space-y-2">
                                            @foreach($otrosGastos as $gasto)
                                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded border border-gray-200">
                                                    <div class="flex-1">
                                                        <span class="text-sm font-medium text-gray-900">{{ $gasto->descripcion ?? ucfirst($gasto->tipo) }}</span>
                                                        <span class="text-xs text-gray-500 ml-2">({{ ucfirst($gasto->tipo) }})</span>
                                                    </div>
                                                    <span class="text-sm font-semibold text-gray-900 ml-4">R${{ number_format($gasto->monto, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                            <span class="text-sm font-semibold text-gray-700">Subtotal Otros:</span>
                                            <span class="text-base font-bold text-gray-600">R${{ number_format($otrosGastos->sum('monto'), 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Total General de Gastos -->
                            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-900">Total Gastos:</span>
                                    <span class="text-2xl font-bold text-red-600">R${{ number_format($repase->total_gastos, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Resumen Financiero -->
            <div class="bg-white rounded-2xl shadow-md border-2 {{ $repase->estado === 'pendiente' ? 'border-red-300' : 'border-green-300' }}">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen Financiero</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">Total Exámenes:</span>
                            <span class="text-sm font-medium text-gray-900">R${{ number_format($repase->total_examenes, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">Total Gastos:</span>
                            <span class="text-sm font-medium text-red-600">-R${{ number_format($repase->total_gastos, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t-2 border-gray-300 pt-3">
                            <span class="text-lg font-bold text-gray-900">Total Neto:</span>
                            <span class="text-2xl font-bold {{ $repase->total_neto >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                R${{ number_format($repase->total_neto, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            @if($repase->estado === 'pendiente')
                <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                    <div class="p-6">
                        <form action="{{ route('repases.destroy', $repase) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este repase? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl">
                                Eliminar Repase
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
