<?php

namespace App\Console\Commands;

use App\Models\Repase;
use Illuminate\Console\Command;

class DetectarGastosDuplicados extends Command
{
    protected $signature = 'repases:detectar-duplicados {--fix : Corregir automáticamente los duplicados}';
    protected $description = 'Detecta repases con gastos duplicados del mismo tipo';

    public function handle()
    {
        $fix = $this->option('fix');
        
        $this->info('Buscando repases con gastos duplicados...');
        $this->newLine();
        
        $repases = Repase::with('gastos', 'clinica')->get();
        $problemasEncontrados = 0;
        
        foreach ($repases as $repase) {
            // Agrupar gastos por descripción
            $gastosPorDescripcion = $repase->gastos->groupBy('descripcion');
            
            $tieneDuplicados = false;
            $detalles = [];
            
            foreach ($gastosPorDescripcion as $descripcion => $gastos) {
                if ($gastos->count() > 1) {
                    $tieneDuplicados = true;
                    $total = $gastos->sum('monto');
                    $detalles[] = [
                        'descripcion' => $descripcion,
                        'cantidad' => $gastos->count(),
                        'total' => $total,
                        'gastos' => $gastos
                    ];
                }
            }
            
            if ($tieneDuplicados) {
                $problemasEncontrados++;
                
                $this->warn("Repase ID: {$repase->id}");
                $this->line("Clínica: {$repase->clinica->nombre}");
                $this->line("Fecha: {$repase->fecha->format('Y-m-d')}");
                $this->line("Total Gastos Registrado: R\${$repase->total_gastos}");
                $this->newLine();
                
                foreach ($detalles as $detalle) {
                    $this->line("  - {$detalle['descripcion']}: {$detalle['cantidad']} entradas, Total: R\$" . number_format($detalle['total'], 2));
                    
                    foreach ($detalle['gastos'] as $gasto) {
                        $this->line("    * ID {$gasto->id}: R\$" . number_format($gasto->monto, 2));
                    }
                }
                
                if ($fix) {
                    $this->info("  Corrigiendo duplicados...");
                    
                    foreach ($detalles as $detalle) {
                        // Mantener solo el primer gasto y sumar los montos
                        $gastosOrdenados = $detalle['gastos']->sortBy('id');
                        $primerGasto = $gastosOrdenados->first();
                        $totalMonto = $detalle['total'];
                        
                        // Actualizar el primer gasto con el total
                        $primerGasto->update(['monto' => $totalMonto]);
                        
                        // Eliminar los demás
                        $gastosOrdenados->skip(1)->each(function($gasto) {
                            $gasto->delete();
                        });
                        
                        $this->line("    ✓ Consolidado en gasto ID {$primerGasto->id} con monto R\$" . number_format($totalMonto, 2));
                    }
                    
                    // Recalcular total de gastos
                    $nuevoTotalGastos = $repase->gastos()->sum('monto');
                    $repase->update(['total_gastos' => $nuevoTotalGastos]);
                    
                    // Recalcular total neto
                    $nuevoTotalNeto = $repase->total_examenes - $nuevoTotalGastos;
                    $repase->update(['total_neto' => $nuevoTotalNeto]);
                    
                    $this->info("  ✓ Totales recalculados");
                }
                
                $this->newLine();
            }
        }
        
        if ($problemasEncontrados === 0) {
            $this->info('✓ No se encontraron repases con gastos duplicados.');
        } else {
            $this->warn("Se encontraron {$problemasEncontrados} repases con gastos duplicados.");
            
            if (!$fix) {
                $this->newLine();
                $this->info('Para corregir automáticamente, ejecute:');
                $this->line('php artisan repases:detectar-duplicados --fix');
            } else {
                $this->newLine();
                $this->info('✓ Duplicados corregidos exitosamente.');
            }
        }
        
        return 0;
    }
}
