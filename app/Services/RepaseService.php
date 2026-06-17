<?php

namespace App\Services;

use App\Models\Repase;
use App\Models\Examen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar la lógica de negocio de Repases
 * 
 * Este servicio maneja la creación, actualización y cálculos relacionados
 * con los repases médicos, asegurando integridad transaccional y precisión
 * en los cálculos financieros.
 */
class RepaseService
{
    /**
     * Crea un nuevo repase con sus exámenes y gastos asociados
     * 
     * Este método ejecuta todas las operaciones dentro de una transacción
     * de base de datos para garantizar la integridad de los datos. Si cualquier
     * operación falla, todos los cambios se revierten automáticamente.
     * 
     * @param array $data Datos del repase incluyendo examenes y gastos
     * @return Repase El repase creado con todas sus relaciones
     * @throws \Exception Si ocurre un error durante la transacción
     */
    public function createRepase(array $data): Repase
    {
        try {
            return DB::transaction(function () use ($data) {
                // Usar el estado proporcionado en el formulario
                $estado = $data['estado'] ?? 'pendiente';
                
                // Normalizar estructura de exámenes (nuevo formato)
                $examenesNormalizados = $this->normalizeExamenes($data['examenes']);
                
                // Normalizar estructura de gastos (nuevo formato)
                $gastosNormalizados = $this->normalizeGastos($data['gastos'] ?? [], $data['nombres_tecnicos'] ?? []);
                
                // Calcular totales
                $totalExamenes = $this->calculateTotalExamenes(
                    $examenesNormalizados,
                    $data['tipo_precio'],
                    $data['clinica_id'] ?? null
                );
                
                $totalGastos = $this->calculateTotalGastos($gastosNormalizados);
                
                $totalNeto = $this->calculateTotalNeto(
                    $totalExamenes,
                    $data['total_consultas'],
                    $totalGastos
                );
                
                // Crear el repase
                $repase = Repase::create([
                    'clinica_id' => $data['clinica_id'],
                    'fecha' => $data['fecha'],
                    'fecha_pago' => $data['fecha_pago'] ?? null,
                    'estado' => $estado,
                    'tipo_precio' => $data['tipo_precio'],
                    'total_examenes' => $totalExamenes,
                    'total_consultas' => $data['total_consultas'],
                    'pedidos_doctor' => $data['pedidos_doctor'] ?? 0,
                    'total_gastos' => $totalGastos,
                    'total_neto' => $totalNeto,
                    'observaciones' => $data['observaciones'] ?? null,
                    'comentarios_operativos' => $data['comentarios']['operativos'] ?? null,
                    'comentarios_administrativos' => $data['comentarios']['administrativos'] ?? null,
                    'comentarios_caja_chica' => $data['comentarios']['caja_chica'] ?? null,
                    'comentarios_insumios_medicos' => $data['comentarios']['insumios_medicos'] ?? null,
                ]);
                
                // Crear los registros de exámenes
                foreach ($examenesNormalizados as $examenData) {
                    $examen = Examen::findOrFail($examenData['examen_id']);
                    
                    // Determinar el precio unitario resuelto por clínica
                    $precioUnitario = $examen->getPrecioParaClinica(
                        $data['clinica_id'] ?? null,
                        $data['tipo_precio'] ?? 'sin_nota'
                    );
                    
                    $subtotal = $examenData['cantidad'] * $precioUnitario;
                    
                    $repase->repaseExamenes()->create([
                        'examen_id' => $examenData['examen_id'],
                        'cantidad' => $examenData['cantidad'],
                        'precio_unitario_usado' => $precioUnitario,
                        'subtotal' => $subtotal,
                    ]);
                }
                
                // Crear los registros de gastos si existen
                if (!empty($gastosNormalizados)) {
                    foreach ($gastosNormalizados as $gastoData) {
                        $repase->gastos()->create([
                            'tipo' => $gastoData['tipo'],
                            'descripcion' => $gastoData['descripcion'] ?? null,
                            'gasto_key' => $gastoData['gasto_key'] ?? null,
                            'monto' => $gastoData['monto'],
                        ]);
                    }
                }
                
                return $repase->load(['clinica', 'repaseExamenes.examen', 'gastos']);
            });
        } catch (\Exception $e) {
            Log::error('Error al crear repase: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
    
    /**
     * Actualiza un repase existente con sus exámenes y gastos
     * 
     * Este método elimina los exámenes y gastos existentes y los recrea
     * con los nuevos datos, recalculando todos los totales. La operación
     * se ejecuta dentro de una transacción para garantizar integridad.
     * 
     * @param Repase $repase El repase a actualizar
     * @param array $data Nuevos datos del repase
     * @return Repase El repase actualizado con todas sus relaciones
     * @throws \Exception Si ocurre un error durante la transacción
     */
    public function updateRepase(Repase $repase, array $data): Repase
    {
        try {
            return DB::transaction(function () use ($repase, $data) {
                // Usar el estado proporcionado en el formulario
                $estado = $data['estado'] ?? 'pendiente';
                
                // Normalizar estructura de exámenes (nuevo formato)
                $examenesNormalizados = $this->normalizeExamenes($data['examenes']);
                
                // Normalizar estructura de gastos (nuevo formato)
                $gastosNormalizados = $this->normalizeGastos($data['gastos'] ?? [], $data['nombres_tecnicos'] ?? []);
                
                // Calcular totales
                $totalExamenes = $this->calculateTotalExamenes(
                    $examenesNormalizados,
                    $data['tipo_precio'],
                    $data['clinica_id'] ?? null
                );
                
                $totalGastos = $this->calculateTotalGastos($gastosNormalizados);
                
                $totalNeto = $this->calculateTotalNeto(
                    $totalExamenes,
                    $data['total_consultas'],
                    $totalGastos
                );
                
                // Actualizar el repase
                $repase->update([
                    'clinica_id' => $data['clinica_id'],
                    'fecha' => $data['fecha'],
                    'fecha_pago' => $data['fecha_pago'] ?? null,
                    'estado' => $estado,
                    'tipo_precio' => $data['tipo_precio'],
                    'total_examenes' => $totalExamenes,
                    'total_consultas' => $data['total_consultas'],
                    'pedidos_doctor' => $data['pedidos_doctor'] ?? 0,
                    'total_gastos' => $totalGastos,
                    'total_neto' => $totalNeto,
                    'observaciones' => $data['observaciones'] ?? null,
                    'comentarios_operativos' => $data['comentarios']['operativos'] ?? null,
                    'comentarios_administrativos' => $data['comentarios']['administrativos'] ?? null,
                    'comentarios_caja_chica' => $data['comentarios']['caja_chica'] ?? null,
                    'comentarios_insumios_medicos' => $data['comentarios']['insumios_medicos'] ?? null,
                ]);
                
                // Eliminar exámenes y gastos existentes
                $repase->repaseExamenes()->delete();
                $repase->gastos()->delete();
                
                // Crear los nuevos registros de exámenes
                foreach ($examenesNormalizados as $examenData) {
                    $examen = Examen::findOrFail($examenData['examen_id']);
                    
                    // Determinar el precio unitario resuelto por clínica
                    $precioUnitario = $examen->getPrecioParaClinica(
                        $data['clinica_id'] ?? null,
                        $data['tipo_precio'] ?? 'sin_nota'
                    );
                    
                    $subtotal = $examenData['cantidad'] * $precioUnitario;
                    
                    $repase->repaseExamenes()->create([
                        'examen_id' => $examenData['examen_id'],
                        'cantidad' => $examenData['cantidad'],
                        'precio_unitario_usado' => $precioUnitario,
                        'subtotal' => $subtotal,
                    ]);
                }
                
                // Crear los nuevos registros de gastos si existen
                if (!empty($gastosNormalizados)) {
                    foreach ($gastosNormalizados as $gastoData) {
                        $repase->gastos()->create([
                            'tipo' => $gastoData['tipo'],
                            'descripcion' => $gastoData['descripcion'] ?? null,
                            'gasto_key' => $gastoData['gasto_key'] ?? null,
                            'monto' => $gastoData['monto'],
                        ]);
                    }
                }
                
                return $repase->load(['clinica', 'repaseExamenes.examen', 'gastos']);
            });
        } catch (\Exception $e) {
            Log::error('Error al actualizar repase: ' . $e->getMessage(), [
                'repase_id' => $repase->id,
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
    
    /**
     * Calcula el total de exámenes según el tipo de precio
     * 
     * Este método suma los subtotales de todos los exámenes, donde cada
     * subtotal se calcula como cantidad × precio_unitario. El precio unitario
     * se determina según el tipo de precio (sin_nota o con_nota).
     * 
     * @param array $examenes Array de exámenes con examen_id y cantidad
     * @param string $tipoPrecio Tipo de precio: 'sin_nota' o 'con_nota'
     * @return float Total de exámenes calculado
     */
    public function calculateTotalExamenes(array $examenes, string $tipoPrecio, ?int $clinicaId = null): float
    {
        $total = 0.0;
        
        foreach ($examenes as $examenData) {
            $examen = Examen::findOrFail($examenData['examen_id']);
            
            // Usar precio resuelto: override por clínica si existe, o global
            $precio = $examen->getPrecioParaClinica($clinicaId, $tipoPrecio);
            
            // Calcular subtotal y sumarlo al total
            $subtotal = $examenData['cantidad'] * $precio;
            $total += $subtotal;
        }
        
        return round($total, 2);
    }
    
    /**
     * Calcula el total de gastos
     * 
     * Este método suma todos los montos de los gastos proporcionados.
     * 
     * @param array $gastos Array de gastos con sus montos
     * @return float Total de gastos calculado
     */
    public function calculateTotalGastos(array $gastos): float
    {
        $total = 0.0;
        
        foreach ($gastos as $gastoData) {
            $total += $gastoData['monto'];
        }
        
        return round($total, 2);
    }
    
    /**
     * Calcula el total neto del repase
     * 
     * El total neto se calcula con la fórmula:
     * total_neto = total_examenes - total_gastos
     * 
     * Este valor representa la ganancia neta del repase médico.
     * Nota: total_consultas ya no se incluye en el cálculo, es solo un campo informativo.
     * 
     * @param float $totalExamenes Total de ingresos por exámenes
     * @param float $totalConsultas Total de consultas (no se usa en el cálculo, solo informativo)
     * @param float $totalGastos Total de gastos operativos
     * @return float Total neto calculado
     */
    public function calculateTotalNeto(float $totalExamenes, float $totalConsultas, float $totalGastos): float
    {
        $totalNeto = $totalExamenes - $totalGastos;
        
        return round($totalNeto, 2);
    }
    
    /**
     * Determina el estado del repase según la fecha de pago
     * 
     * Si existe una fecha de pago, el estado es "pagado".
     * Si no existe fecha de pago, el estado es "pendiente".
     * 
     * @param string|null $fechaPago Fecha de pago del repase
     * @return string Estado del repase: 'pagado' o 'pendiente'
     */
    public function determineEstado(?string $fechaPago): string
    {
        return $fechaPago !== null ? 'pagado' : 'pendiente';
    }

    /**
     * Normaliza la estructura de exámenes del nuevo formato al formato esperado
     * 
     * Convierte de: ['1' => ['cantidad' => 5, 'examen_id' => 1], '2' => ['cantidad' => 0, 'examen_id' => 2]]
     * A: [['examen_id' => 1, 'cantidad' => 5]]
     * 
     * @param array $examenes Array de exámenes en nuevo formato
     * @return array Array normalizado de exámenes
     */
    private function normalizeExamenes(array $examenes): array
    {
        $normalized = [];
        
        foreach ($examenes as $examenId => $examenData) {
            // Si es el nuevo formato (objeto con cantidad y examen_id)
            if (is_array($examenData) && isset($examenData['cantidad'])) {
                $cantidad = (int) $examenData['cantidad'];
                $id = isset($examenData['examen_id']) ? (int) $examenData['examen_id'] : (int) $examenId;
                
                // Solo agregar si la cantidad es mayor a 0
                if ($cantidad > 0) {
                    $normalized[] = [
                        'examen_id' => $id,
                        'cantidad' => $cantidad,
                    ];
                }
            }
            // Si es el formato antiguo (ya tiene examen_id y cantidad)
            elseif (is_array($examenData) && isset($examenData['examen_id'], $examenData['cantidad'])) {
                if ((int) $examenData['cantidad'] > 0) {
                    $normalized[] = [
                        'examen_id' => (int) $examenData['examen_id'],
                        'cantidad' => (int) $examenData['cantidad'],
                    ];
                }
            }
        }
        
        return $normalized;
    }

    /**
     * Normaliza la estructura de gastos del nuevo formato al formato esperado
     * 
     * Convierte de: ['honorarios_medicos' => 100, 'gasolina_equipo' => 50]
     * A: [['tipo' => 'doctor', 'descripcion' => 'Honorarios Médicos', 'monto' => 100], ['tipo' => 'gasolina', 'descripcion' => 'Gasolina Equipo', 'monto' => 50]]
     * 
     * @param array $gastos Array de gastos en nuevo formato
     * @param array $nombresTecnicos Array opcional con nombres personalizados [1 => 'María', 2 => 'Juan']
     * @return array Array normalizado de gastos
     */
    private function normalizeGastos(array $gastos, array $nombresTecnicos = []): array
    {
        $normalized = [];
        
        // Mapeo de nuevos tipos a tipos ENUM existentes
        $tipoMap = [
            // Gastos Operativos -> doctor/tecnico/laudos/gasolina
            'honorarios_medicos' => ['tipo' => 'doctor', 'descripcion' => 'Honorarios Médicos'],
            'honorarios_tecnico_1' => ['tipo' => 'tecnico', 'descripcion' => 'Honorarios Técnico Enfermero 1'],
            'honorarios_tecnico_2' => ['tipo' => 'tecnico', 'descripcion' => 'Honorarios Técnico Enfermero 2'],
            'honorarios_laudos_egg' => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudos EGG'],
            'honorarios_laudos_potencial' => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudos Potencial'],
            'honorarios_laudo_electromiografia' => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudo Electromiografía'],
            'honorarios_motorista' => ['tipo' => 'extra', 'descripcion' => 'Honorarios Motorista'],
            'gasolina_equipo' => ['tipo' => 'gasolina', 'descripcion' => 'Gasolina Equipo'],
            'gasolina_medico' => ['tipo' => 'gasolina', 'descripcion' => 'Gasolina Médico'],
            // Gastos Administrativos -> extra
            'software_medico' => ['tipo' => 'extra', 'descripcion' => 'Software Médico'],
            'alquiler_movilidad' => ['tipo' => 'extra', 'descripcion' => 'Alquiler Movilidad'],
            'mantenimiento_equipos' => ['tipo' => 'extra', 'descripcion' => 'Mantenimiento Equipos'],
            // Caja Chica -> extra
            'alimentacion_medico' => ['tipo' => 'extra', 'descripcion' => 'Alimentación Médico'],
            'alimentacion_personal' => ['tipo' => 'extra', 'descripcion' => 'Alimentación Personal'],
            'hospedajes' => ['tipo' => 'extra', 'descripcion' => 'Hospedajes'],
            'estacionamiento' => ['tipo' => 'extra', 'descripcion' => 'Estacionamiento'],
            'papeleria' => ['tipo' => 'extra', 'descripcion' => 'Papelería'],
            'pedagio_medico' => ['tipo' => 'extra', 'descripcion' => 'Pedagio Médico'],
            'pedagios_personal' => ['tipo' => 'extra', 'descripcion' => 'Pedagios Personal'],
            'otros_caja_chica' => ['tipo' => 'extra', 'descripcion' => 'Otros'],
            // Insumios Médicos -> extra
            'electrodos' => ['tipo' => 'extra', 'descripcion' => 'Electrodos'],
            'agujas_medicas' => ['tipo' => 'extra', 'descripcion' => 'Agujas Médicas'],
            'gel' => ['tipo' => 'extra', 'descripcion' => 'Gel'],
            'guantes_latex' => ['tipo' => 'extra', 'descripcion' => 'Guantes Latex'],
        ];
        
        // Si es el nuevo formato (objeto plano con claves como tipos de gasto)
        if (!empty($gastos) && !isset($gastos[0])) {
            foreach ($gastos as $tipoKey => $monto) {
                $montoFloat = (float) $monto;
                
                // Solo agregar si el monto es mayor a 0
                if ($montoFloat > 0) {
                    // Si existe en el mapeo, usar el tipo y descripción mapeados
                    if (isset($tipoMap[$tipoKey])) {
                        $descripcion = $tipoMap[$tipoKey]['descripcion'];
                        
                        // Permitir nombres personalizados para técnicos enfermeros
                        if ($tipoKey === 'honorarios_tecnico_1' && !empty($nombresTecnicos[1])) {
                            $descripcion = $nombresTecnicos[1];
                        } elseif ($tipoKey === 'honorarios_tecnico_2' && !empty($nombresTecnicos[2])) {
                            $descripcion = $nombresTecnicos[2];
                        }
                        
                        $normalized[] = [
                            'tipo' => $tipoMap[$tipoKey]['tipo'],
                            'descripcion' => $descripcion,
                            'gasto_key' => $tipoKey,
                            'monto' => $montoFloat,
                        ];
                    }
                    // Si no existe en el mapeo, usar 'extra' como tipo por defecto
                    else {
                        $normalized[] = [
                            'tipo' => 'extra',
                            'descripcion' => ucfirst(str_replace('_', ' ', $tipoKey)),
                            'gasto_key' => $tipoKey,
                            'monto' => $montoFloat,
                        ];
                    }
                }
            }
        }
        // Si es el formato antiguo (array de objetos con tipo, descripcion, monto)
        else {
            foreach ($gastos as $gasto) {
                if (is_array($gasto) && isset($gasto['tipo'], $gasto['monto'])) {
                    if ((float) $gasto['monto'] > 0) {
                        $normalized[] = [
                            'tipo' => $gasto['tipo'],
                            'descripcion' => $gasto['descripcion'] ?? null,
                            'monto' => (float) $gasto['monto'],
                        ];
                    }
                }
            }
        }
        
        return $normalized;
    }
}