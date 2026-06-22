<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RepaseSeeder extends Seeder
{
    /**
     * Seed repases with their examenes and gastos, distributed across the
     * last 12 months. Each repase carries its empresa_id explicitly so the
     * tenant scoping is correct regardless of the EmpresaContext state.
     *
     * Idempotent at the month level: skips a (clinica, month) pair when a
     * repase already exists for it. This lets you re-run the seeder to fill
     * gaps without generating duplicates.
     */
    public function run(): void
    {
        $clinicas = Clinica::all();

        if ($clinicas->isEmpty()) {
            $this->command->error('No hay clínicas. Ejecutá ClinicaSeeder primero.');

            return;
        }

        // Cache examenes keyed by empresa for efficient lookup.
        $examenesByEmpresa = Examen::all()->groupBy('empresa_id');

        $totalRepases = 0;

        for ($mes = 11; $mes >= 0; $mes--) {
            $fechaBase = now()->subMonths($mes)->startOfMonth();
            $diasEnMes = $fechaBase->daysInMonth;

            foreach ($clinicas as $clinica) {
                $empresaId = $clinica->empresa_id;
                $examenes = $examenesByEmpresa->get($empresaId);

                if ($examenes === null || $examenes->isEmpty()) {
                    continue;
                }

                // Skip if this clinica already has a repase in this month.
                $alreadyHas = Repase::where('clinica_id', $clinica->id)
                    ->whereYear('fecha', $fechaBase->year)
                    ->whereMonth('fecha', $fechaBase->month)
                    ->exists();
                if ($alreadyHas) {
                    continue;
                }

                $factorEstacional = $this->getSeasonalFactor($fechaBase->month);
                $repasesPorMes = (int) round(rand(8, 15) * $factorEstacional);

                for ($i = 0; $i < $repasesPorMes; $i++) {
                    $repase = $this->createRepase($clinica, $empresaId, $examenes, $fechaBase, $diasEnMes);
                    if ($repase) {
                        $totalRepases++;
                    }
                }
            }
        }

        $this->command->info("{$totalRepases} repases creados con exámenes y gastos (total: " . Repase::count() . ').');
    }

    private function createRepase(Clinica $clinica, int $empresaId, $examenes, Carbon $fechaBase, int $diasEnMes): ?Repase
    {
        $tipoPrecio = ['sin_nota', 'con_nota'][rand(0, 1)];
        $estado = rand(0, 100) < 85 ? 'pagado' : 'pendiente';

        $diaDelMes = rand(1, $diasEnMes);
        $fecha = $fechaBase->copy()->addDays($diaDelMes - 1);

        $fechaPago = $estado === 'pagado'
            ? $fecha->copy()->addDays(rand(1, 20))
            : (rand(0, 1) ? $fecha->copy()->addDays(rand(15, 45)) : null);

        $totalConsultas = rand(0, 60);

        $numExamenes = rand(1, 5);
        $examenesSeleccionados = $examenes->random($numExamenes);
        $totalExamenes = 0;
        $examenesData = [];

        foreach ($examenesSeleccionados as $examen) {
            $cantidad = $this->getCantidadPorExamen($examen->nombre);
            $precioUnitario = $tipoPrecio === 'sin_nota'
                ? $examen->precio_sin_nota
                : $examen->precio_con_nota;

            $variacionPrecio = 1 + (rand(-5, 5) / 100);
            $precioUnitario = round($precioUnitario * $variacionPrecio, 2);

            $subtotal = $cantidad * $precioUnitario;
            $totalExamenes += $subtotal;

            $examenesData[] = [
                'examen_id' => $examen->id,
                'cantidad' => $cantidad,
                'precio_unitario_usado' => $precioUnitario,
                'subtotal' => $subtotal,
            ];
        }

        $gastosData = $this->generarGastosRealistas($totalExamenes);
        $totalGastos = array_sum(array_column($gastosData, 'monto'));
        $totalNeto = $totalExamenes - $totalGastos;

        $repase = Repase::create([
            'empresa_id' => $empresaId,
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
            'fecha_pago' => $fechaPago,
            'estado' => $estado,
            'tipo_precio' => $tipoPrecio,
            'total_examenes' => $totalExamenes,
            'total_consultas' => $totalConsultas,
            'pedidos_doctor' => rand(0, 10),
            'total_gastos' => $totalGastos,
            'total_neto' => $totalNeto,
            'observaciones' => rand(0, 100) < 20 ? $this->generarObservacion() : null,
        ]);

        foreach ($examenesData as $examenData) {
            $repase->repaseExamenes()->create([
                'empresa_id' => $empresaId,
                'examen_id' => $examenData['examen_id'],
                'cantidad' => $examenData['cantidad'],
                'precio_unitario_usado' => $examenData['precio_unitario_usado'],
                'subtotal' => $examenData['subtotal'],
            ]);
        }

        foreach ($gastosData as $gastoData) {
            $repase->gastos()->create(array_merge(['empresa_id' => $empresaId], $gastoData));
        }

        return $repase;
    }

    private function getSeasonalFactor(int $mes): float
    {
        $factores = [
            1 => 0.9, 2 => 0.95, 3 => 1.1, 4 => 1.15, 5 => 1.2, 6 => 1.1,
            7 => 0.85, 8 => 0.8, 9 => 1.25, 10 => 1.2, 11 => 1.1, 12 => 0.9,
        ];

        return $factores[$mes] ?? 1.0;
    }

    private function getCantidadPorExamen(string $nombreExamen): int
    {
        $nombre = strtolower($nombreExamen);

        if (str_contains($nombre, 'electroencefalograma c/ mapeo')) {
            return rand(1, 5);
        } elseif (str_contains($nombre, 'electroencefalograma c/ mapa')) {
            return rand(2, 10);
        } elseif (str_contains($nombre, 'electroencefalograma')) {
            return rand(3, 15);
        } elseif (str_contains($nombre, 'electroneuromiografia')) {
            return rand(1, 8);
        } elseif (str_contains($nombre, 'potencial evocado')) {
            return rand(1, 10);
        }

        return rand(2, 15);
    }

    private function generarGastosRealistas(float $ingresosBrutos): array
    {
        $gastos = [];

        $gastos[] = [
            'tipo' => 'doctor',
            'descripcion' => 'Honorarios médicos',
            'monto' => round($ingresosBrutos * (rand(30, 45) / 100), 2),
        ];

        $gastos[] = [
            'tipo' => 'tecnico',
            'descripcion' => 'Honorarios técnicos',
            'monto' => round($ingresosBrutos * (rand(15, 25) / 100), 2),
        ];

        if (rand(0, 100) < 70) {
            $gastos[] = [
                'tipo' => 'laudos',
                'descripcion' => 'Interpretación de estudios',
                'monto' => round($ingresosBrutos * (rand(5, 10) / 100), 2),
            ];
        }

        if (rand(0, 100) < 80) {
            $gastos[] = [
                'tipo' => 'gasolina',
                'descripcion' => 'Combustible y transporte',
                'monto' => round($ingresosBrutos * (rand(2, 5) / 100), 2),
            ];
        }

        if (rand(0, 100) < 30) {
            $gastos[] = [
                'tipo' => 'extra',
                'descripcion' => $this->generarDescripcionGastoExtra(),
                'monto' => round($ingresosBrutos * (rand(1, 3) / 100), 2),
            ];
        }

        return $gastos;
    }

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
