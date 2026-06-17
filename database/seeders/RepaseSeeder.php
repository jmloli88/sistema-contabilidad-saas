<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RepaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clinicas = Clinica::all();
        $examenes = Examen::all();

        if ($clinicas->isEmpty() || $examenes->isEmpty()) {
            $this->command->error('Debe ejecutar primero ClinicaSeeder y ExamenSeeder');
            return;
        }

        $totalRepases = 0;
        
        // Generar datos para los últimos 12 meses
        for ($mes = 11; $mes >= 0; $mes--) {
            $fechaBase = now()->subMonths($mes)->startOfMonth();
            $diasEnMes = $fechaBase->daysInMonth;
            
            // Generar entre 8-15 repases por mes por clínica (variación estacional)
            foreach ($clinicas as $clinica) {
                // Variación estacional: más actividad en ciertos meses
                $factorEstacional = $this->getSeasonalFactor($fechaBase->month);
                $repasesPorMes = (int) (rand(8, 15) * $factorEstacional);
                
                for ($i = 0; $i < $repasesPorMes; $i++) {
                    $tipoPrecio = ['sin_nota', 'con_nota'][rand(0, 1)];
                    $estado = rand(0, 100) < 85 ? 'pagado' : 'pendiente'; // 85% pagados
                    
                    // Fecha del repase (distribuida a lo largo del mes)
                    $diaDelMes = rand(1, $diasEnMes);
                    $fecha = $fechaBase->copy()->addDays($diaDelMes - 1);
                    
                    // Fecha de pago
                    if ($estado === 'pagado') {
                        $fechaPago = $fecha->copy()->addDays(rand(1, 20));
                    } else {
                        $fechaPago = rand(0, 1) ? $fecha->copy()->addDays(rand(15, 45)) : null;
                    }

                    // Calcular totales con variación realista
                    $totalConsultas = rand(0, 60); // Cantidad de consultas (0-60)
                    
                    // Seleccionar 1-5 exámenes aleatorios (más variedad)
                    $numExamenes = rand(1, 5);
                    $examenesSeleccionados = $examenes->random($numExamenes);
                    $totalExamenes = 0;
                    $examenesData = [];
                    
                    foreach ($examenesSeleccionados as $examen) {
                        // Cantidad variable según el tipo de examen
                        $cantidad = $this->getCantidadPorExamen($examen->nombre);
                        $precioUnitario = $tipoPrecio === 'sin_nota' 
                            ? $examen->precio_sin_nota 
                            : $examen->precio_con_nota;
                        
                        // Aplicar pequeña variación en precios (±5%)
                        $variacionPrecio = 1 + (rand(-5, 5) / 100);
                        $precioUnitario = round($precioUnitario * $variacionPrecio, 2);
                        
                        $subtotal = $cantidad * $precioUnitario;
                        $totalExamenes += $subtotal;
                        
                        $examenesData[] = [
                            'examen_id' => $examen->id,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'subtotal' => $subtotal,
                        ];
                    }
                    
                    // Crear gastos más realistas
                    $gastosData = $this->generarGastosRealistas($totalExamenes);
                    $totalGastos = array_sum(array_column($gastosData, 'monto'));
                    
                    $totalNeto = $totalExamenes - $totalGastos;
                    
                    // Crear el repase
                    $repase = Repase::create([
                        'clinica_id' => $clinica->id,
                        'fecha' => $fecha,
                        'fecha_pago' => $fechaPago,
                        'estado' => $estado,
                        'tipo_precio' => $tipoPrecio,
                        'total_examenes' => $totalExamenes,
                        'total_consultas' => $totalConsultas,
                        'total_gastos' => $totalGastos,
                        'total_neto' => $totalNeto,
                        'observaciones' => rand(0, 100) < 20 ? $this->generarObservacion() : null,
                    ]);
                    
                    // Crear los exámenes del repase
                    foreach ($examenesData as $examenData) {
                        $repase->repaseExamenes()->create([
                            'examen_id' => $examenData['examen_id'],
                            'cantidad' => $examenData['cantidad'],
                            'precio_unitario_usado' => $examenData['precio_unitario'],
                            'subtotal' => $examenData['subtotal'],
                        ]);
                    }
                    
                    // Crear los gastos del repase
                    foreach ($gastosData as $gastoData) {
                        $repase->gastos()->create($gastoData);
                    }
                    
                    $totalRepases++;
                }
            }
        }
        
        $this->command->info("{$totalRepases} repases creados exitosamente para los últimos 12 meses con sus exámenes y gastos.");
    }
    
    /**
     * Obtiene el factor estacional para un mes dado
     */
    private function getSeasonalFactor(int $mes): float
    {
        // Simular patrones estacionales realistas
        $factores = [
            1 => 0.9,   // Enero - post-navidad, menos actividad
            2 => 0.95,  // Febrero
            3 => 1.1,   // Marzo - incremento primaveral
            4 => 1.15,  // Abril - alta actividad
            5 => 1.2,   // Mayo - pico de actividad
            6 => 1.1,   // Junio
            7 => 0.85,  // Julio - vacaciones
            8 => 0.8,   // Agosto - vacaciones
            9 => 1.25,  // Septiembre - regreso de vacaciones
            10 => 1.2,  // Octubre - alta actividad
            11 => 1.1,  // Noviembre
            12 => 0.9,  // Diciembre - navidad
        ];
        
        return $factores[$mes] ?? 1.0;
    }
    
    /**
     * Obtiene cantidad realista según el tipo de examen
     */
    private function getCantidadPorExamen(string $nombreExamen): int
    {
        $nombre = strtolower($nombreExamen);
        
        // Exámenes más comunes tienen cantidades más altas
        if (str_contains($nombre, 'rayos') || str_contains($nombre, 'radiograf')) {
            return rand(5, 25);
        } elseif (str_contains($nombre, 'ecograf') || str_contains($nombre, 'ultrason')) {
            return rand(3, 15);
        } elseif (str_contains($nombre, 'tomograf') || str_contains($nombre, 'resonan')) {
            return rand(1, 8);
        } elseif (str_contains($nombre, 'laboratorio') || str_contains($nombre, 'sangre')) {
            return rand(10, 40);
        } else {
            return rand(2, 20);
        }
    }
    
    /**
     * Genera gastos realistas basados en los ingresos
     */
    private function generarGastosRealistas(float $ingresosBrutos): array
    {
        $gastos = [];
        
        // Gasto de doctor (30-45% de ingresos brutos)
        $porcentajeDoctor = rand(30, 45) / 100;
        $gastos[] = [
            'tipo' => 'doctor',
            'descripcion' => 'Honorarios médicos',
            'monto' => round($ingresosBrutos * $porcentajeDoctor, 2),
        ];
        
        // Gasto de técnico (15-25% de ingresos brutos)
        $porcentajeTecnico = rand(15, 25) / 100;
        $gastos[] = [
            'tipo' => 'tecnico',
            'descripcion' => 'Honorarios técnicos',
            'monto' => round($ingresosBrutos * $porcentajeTecnico, 2),
        ];
        
        // Laudos (5-10% de ingresos brutos)
        if (rand(0, 100) < 70) { // 70% de probabilidad
            $porcentajeLaudos = rand(5, 10) / 100;
            $gastos[] = [
                'tipo' => 'laudos',
                'descripcion' => 'Interpretación de estudios',
                'monto' => round($ingresosBrutos * $porcentajeLaudos, 2),
            ];
        }
        
        // Gasolina (2-5% de ingresos brutos)
        if (rand(0, 100) < 80) { // 80% de probabilidad
            $porcentajeGasolina = rand(2, 5) / 100;
            $gastos[] = [
                'tipo' => 'gasolina',
                'descripcion' => 'Combustible y transporte',
                'monto' => round($ingresosBrutos * $porcentajeGasolina, 2),
            ];
        }
        
        // Gastos extra ocasionales (1-3% de ingresos brutos)
        if (rand(0, 100) < 30) { // 30% de probabilidad
            $porcentajeExtra = rand(1, 3) / 100;
            $gastos[] = [
                'tipo' => 'extra',
                'descripcion' => $this->generarDescripcionGastoExtra(),
                'monto' => round($ingresosBrutos * $porcentajeExtra, 2),
            ];
        }
        
        return $gastos;
    }
    
    /**
     * Genera una observación aleatoria
     */
    private function generarObservacion(): string
    {
        $observaciones = [
            'Repase procesado sin incidencias',
            'Documentación completa',
            'Requiere seguimiento administrativo',
            'Paciente VIP - atención especial',
            'Estudio urgente procesado',
            'Revisión de calidad aprobada',
            'Facturación verificada',
            'Proceso estándar completado',
        ];
        
        return $observaciones[array_rand($observaciones)];
    }
    
    /**
     * Genera descripción para gastos extra
     */
    private function generarDescripcionGastoExtra(): string
    {
        $descripciones = [
            'Material de oficina',
            'Mantenimiento de equipos',
            'Servicios de limpieza',
            'Reparaciones menores',
            'Suministros médicos',
            'Capacitación del personal',
            'Servicios de mensajería',
            'Gastos de representación',
        ];
        
        return $descripciones[array_rand($descripciones)];
    }
}
