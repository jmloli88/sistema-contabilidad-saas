<?php

namespace App\Console\Commands;

use App\Models\Gasto;
use Illuminate\Console\Command;

class CorregirGastosTecnicosAntiguos extends Command
{
    protected $signature = 'repases:corregir-gastos-tecnicos {--dry-run : Mostrar cambios sin aplicarlos}';
    protected $description = 'Corrige gastos técnicos con descripciones antiguas';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Buscar gastos con descripciones antiguas que necesitan corrección
        $gastosAntiguos = Gasto::where(function ($query) {
            $query->where('descripcion', 'LIKE', '%Honorarios técnicos%')
                  ->orWhere('descripcion', 'LIKE', '%Honorarios tecnicos%')
                  ->orWhere('descripcion', '=', 'Honorarios Técnicos')
                  ->orWhere('descripcion', '=', 'Honorarios Tecnicos');
        })
        ->where('tipo', 'tecnico')
        ->get();

        if ($gastosAntiguos->isEmpty()) {
            $this->info('No se encontraron gastos técnicos con descripciones antiguas.');
            return 0;
        }

        $this->info("Se encontraron {$gastosAntiguos->count()} gastos técnicos con descripciones antiguas:");
        $this->newLine();

        foreach ($gastosAntiguos as $gasto) {
            $this->line("ID: {$gasto->id}");
            $this->line("  Repase ID: {$gasto->repase_id}");
            $this->line("  Descripción actual: {$gasto->descripcion}");
            $this->line("  Monto: R\$" . number_format($gasto->monto, 2));
            
            if (!$dryRun) {
                // Cambiar a "Honorarios Técnico Enfermero 1" por defecto
                $gasto->descripcion = 'Honorarios Técnico Enfermero 1';
                $gasto->save();
                $this->info("  ✓ Actualizado a: {$gasto->descripcion}");
            } else {
                $this->warn("  → Se cambiaría a: Honorarios Técnico Enfermero 1");
            }
            
            $this->newLine();
        }

        if ($dryRun) {
            $this->warn('Modo dry-run: No se aplicaron cambios. Ejecuta sin --dry-run para aplicar los cambios.');
        } else {
            $this->info("✓ Se actualizaron {$gastosAntiguos->count()} gastos técnicos.");
        }

        return 0;
    }
}
