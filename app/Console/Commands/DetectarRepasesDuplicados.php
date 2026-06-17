<?php

namespace App\Console\Commands;

use App\Models\Repase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectarRepasesDuplicados extends Command
{
    protected $signature = 'repases:detectar-repases-duplicados {--show-details : Mostrar detalles de los repases duplicados}';
    protected $description = 'Detecta repases que podrían ser duplicados (misma fecha, clínica y exámenes similares)';

    public function handle()
    {
        $showDetails = $this->option('show-details');
        
        $this->info('Buscando repases potencialmente duplicados...');
        $this->newLine();
        
        // Buscar repases con la misma fecha y clínica
        $repases = Repase::with(['clinica', 'repaseExamenes.examen'])
            ->orderBy('fecha', 'desc')
            ->orderBy('clinica_id')
            ->orderBy('created_at')
            ->get();
        
        $grupos = $repases->groupBy(function($repase) {
            return $repase->fecha->format('Y-m-d') . '_' . $repase->clinica_id;
        });
        
        $duplicadosEncontrados = 0;
        
        foreach ($grupos as $key => $grupo) {
            if ($grupo->count() > 1) {
                $duplicadosEncontrados++;
                
                [$fecha, $clinicaId] = explode('_', $key);
                $clinica = $grupo->first()->clinica;
                
                $this->warn("Grupo de posibles duplicados:");
                $this->line("Fecha: {$fecha}");
                $this->line("Clínica: {$clinica->nombre}");
                $this->line("Cantidad de repases: {$grupo->count()}");
                $this->newLine();
                
                $tabla = [];
                foreach ($grupo as $repase) {
                    $examenes = $repase->repaseExamenes->map(function($re) {
                        return "{$re->examen->nombre} ({$re->cantidad})";
                    })->join(', ');
                    
                    $tabla[] = [
                        'ID' => $repase->id,
                        'Creado' => $repase->created_at->format('Y-m-d H:i:s'),
                        'Total Exámenes' => 'R$' . number_format($repase->total_examenes, 2),
                        'Total Gastos' => 'R$' . number_format($repase->total_gastos, 2),
                        'Total Neto' => 'R$' . number_format($repase->total_neto, 2),
                        'Estado' => $repase->estado,
                    ];
                }
                
                $this->table(
                    ['ID', 'Creado', 'Total Exámenes', 'Total Gastos', 'Total Neto', 'Estado'],
                    $tabla
                );
                
                if ($showDetails) {
                    $this->info("Detalles de exámenes:");
                    foreach ($grupo as $repase) {
                        $this->line("  Repase ID {$repase->id}:");
                        foreach ($repase->repaseExamenes as $re) {
                            $this->line("    - {$re->examen->nombre}: {$re->cantidad} x R\$" . number_format($re->precio_unitario_usado, 2) . " = R\$" . number_format($re->subtotal, 2));
                        }
                    }
                    $this->newLine();
                }
                
                // Analizar diferencias
                $tiempoEntreCreaciones = [];
                $repasesOrdenados = $grupo->sortBy('created_at')->values();
                for ($i = 1; $i < $repasesOrdenados->count(); $i++) {
                    $diff = $repasesOrdenados[$i]->created_at->diffInSeconds($repasesOrdenados[$i-1]->created_at);
                    $tiempoEntreCreaciones[] = $diff;
                }
                
                if (!empty($tiempoEntreCreaciones)) {
                    $minDiff = min($tiempoEntreCreaciones);
                    if ($minDiff < 5) {
                        $this->error("  ⚠ Repases creados con menos de 5 segundos de diferencia - Probable doble clic");
                    } elseif ($minDiff < 30) {
                        $this->warn("  ⚠ Repases creados con menos de 30 segundos de diferencia - Posible doble envío");
                    }
                }
                
                // Comparar exámenes
                $primerosExamenes = $repasesOrdenados->first()->repaseExamenes->pluck('cantidad', 'examen_id')->toArray();
                $todosIguales = true;
                
                foreach ($repasesOrdenados->skip(1) as $repase) {
                    $examenes = $repase->repaseExamenes->pluck('cantidad', 'examen_id')->toArray();
                    if ($primerosExamenes != $examenes) {
                        $todosIguales = false;
                        break;
                    }
                }
                
                if ($todosIguales) {
                    $this->error("  ⚠ Todos los repases tienen exactamente los mismos exámenes - Muy probable duplicado");
                } else {
                    $this->info("  ℹ Los exámenes son diferentes - Podrían ser repases legítimos");
                }
                
                $this->newLine();
                $this->line(str_repeat('-', 80));
                $this->newLine();
            }
        }
        
        if ($duplicadosEncontrados === 0) {
            $this->info('✓ No se encontraron repases potencialmente duplicados.');
        } else {
            $this->warn("Se encontraron {$duplicadosEncontrados} grupos de posibles duplicados.");
            $this->newLine();
            $this->info('Recomendaciones:');
            $this->line('1. Revise manualmente cada grupo para confirmar si son duplicados');
            $this->line('2. Si son duplicados por doble clic, elimine el repase con estado "pendiente"');
            $this->line('3. Si ambos están pagados, contacte al usuario para confirmar cuál es el correcto');
            $this->newLine();
            $this->info('Para ver más detalles, ejecute:');
            $this->line('php artisan repases:detectar-repases-duplicados --show-details');
        }
        
        return 0;
    }
}
