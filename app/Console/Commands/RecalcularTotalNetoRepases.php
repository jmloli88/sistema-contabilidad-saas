<?php

namespace App\Console\Commands;

use App\Models\Repase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularTotalNetoRepases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repases:recalcular-total-neto 
                            {--dry-run : Ejecutar sin guardar cambios para ver qué se actualizaría}
                            {--force : Forzar la actualización sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el total_neto de todos los repases usando la nueva fórmula: total_examenes - total_gastos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('===========================================');
        $this->info('Recalcular Total Neto de Repases');
        $this->info('===========================================');
        $this->newLine();

        // Obtener todos los repases
        $repases = Repase::withTrashed()->get();
        $totalRepases = $repases->count();

        if ($totalRepases === 0) {
            $this->warn('No hay repases en la base de datos.');
            return 0;
        }

        $this->info("Total de repases encontrados: {$totalRepases}");
        $this->newLine();

        // Mostrar algunos ejemplos de lo que se va a cambiar
        $this->info('Ejemplos de cambios (primeros 5 repases):');
        $this->table(
            ['ID', 'Total Exámenes', 'Total Consultas', 'Total Gastos', 'Total Neto Actual', 'Total Neto Nuevo'],
            $repases->take(5)->map(function ($repase) {
                $nuevoTotalNeto = round($repase->total_examenes - $repase->total_gastos, 2);
                return [
                    $repase->id,
                    'R$' . number_format($repase->total_examenes, 2),
                    number_format($repase->total_consultas, 0), // Ahora es cantidad, no monto
                    'R$' . number_format($repase->total_gastos, 2),
                    'R$' . number_format($repase->total_neto, 2),
                    'R$' . number_format($nuevoTotalNeto, 2),
                ];
            })->toArray()
        );
        $this->newLine();

        // Calcular estadísticas
        $repasesConCambios = $repases->filter(function ($repase) {
            $nuevoTotalNeto = round($repase->total_examenes - $repase->total_gastos, 2);
            return abs($repase->total_neto - $nuevoTotalNeto) > 0.01; // Tolerancia de 1 centavo
        });

        $cantidadConCambios = $repasesConCambios->count();
        $this->info("Repases que necesitan actualización: {$cantidadConCambios} de {$totalRepases}");
        $this->newLine();

        if ($cantidadConCambios === 0) {
            $this->info('✓ Todos los repases ya tienen el total_neto correcto.');
            return 0;
        }

        // Modo dry-run
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se guardarán cambios.');
            $this->info('Ejecuta el comando sin --dry-run para aplicar los cambios.');
            return 0;
        }

        // Confirmación
        if (!$force) {
            if (!$this->confirm("¿Deseas actualizar {$cantidadConCambios} repases?")) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Actualizar repases
        $this->info('Actualizando repases...');
        $progressBar = $this->output->createProgressBar($cantidadConCambios);
        $progressBar->start();

        $actualizados = 0;
        $errores = 0;

        DB::transaction(function () use ($repasesConCambios, $progressBar, &$actualizados, &$errores) {
            foreach ($repasesConCambios as $repase) {
                try {
                    $nuevoTotalNeto = round($repase->total_examenes - $repase->total_gastos, 2);
                    
                    // Actualizar sin disparar eventos ni timestamps
                    DB::table('repases')
                        ->where('id', $repase->id)
                        ->update(['total_neto' => $nuevoTotalNeto]);
                    
                    $actualizados++;
                } catch (\Exception $e) {
                    $errores++;
                    $this->error("\nError al actualizar repase ID {$repase->id}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('===========================================');
        $this->info('Resumen de la operación:');
        $this->info('===========================================');
        $this->info("✓ Repases actualizados: {$actualizados}");
        
        if ($errores > 0) {
            $this->error("✗ Errores: {$errores}");
        }
        
        $this->newLine();
        $this->info('¡Proceso completado!');

        return 0;
    }
}
