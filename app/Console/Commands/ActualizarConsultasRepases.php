<?php

namespace App\Console\Commands;

use App\Models\Repase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarConsultasRepases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repases:actualizar-consultas
                            {--dry-run : Ejecutar en modo de prueba sin guardar cambios}
                            {--force : Omitir confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el campo total_consultas de todos los repases para que contenga cantidad de consultas (0-60) en lugar de montos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=================================================');
        $this->info('  Actualización de Consultas en Repases');
        $this->info('=================================================');
        $this->newLine();

        // Obtener todos los repases
        $repases = Repase::all();
        $totalRepases = $repases->count();

        if ($totalRepases === 0) {
            $this->warn('No se encontraron repases en la base de datos.');
            return 0;
        }

        $this->info("Total de repases encontrados: {$totalRepases}");
        $this->newLine();

        // Mostrar ejemplos de cambios
        $this->info('Ejemplos de cambios que se realizarán:');
        $this->newLine();

        $ejemplos = $repases->take(5);
        $table = [];

        foreach ($ejemplos as $repase) {
            $nuevoValor = rand(0, 60);
            $table[] = [
                'ID' => $repase->id,
                'Clínica' => $repase->clinica->nombre ?? 'N/A',
                'Fecha' => $repase->fecha,
                'Valor Actual' => $repase->total_consultas,
                'Nuevo Valor' => $nuevoValor,
            ];
        }

        $this->table(
            ['ID', 'Clínica', 'Fecha', 'Valor Actual', 'Nuevo Valor'],
            $table
        );

        if ($totalRepases > 5) {
            $this->info("... y " . ($totalRepases - 5) . " repases más.");
        }
        $this->newLine();

        // Modo dry-run
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se guardarán cambios.');
            $this->info('Ejecuta el comando sin --dry-run para aplicar los cambios.');
            return 0;
        }

        // Confirmación
        if (!$force) {
            $this->warn('ADVERTENCIA: Esta operación actualizará el campo total_consultas de TODOS los repases.');
            $this->warn('Los valores actuales serán reemplazados por cantidades aleatorias entre 0 y 60.');
            $this->newLine();

            if (!$this->confirm('¿Deseas continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Ejecutar actualización dentro de una transacción
        $this->info('Iniciando actualización...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalRepases);
        $progressBar->start();

        $actualizados = 0;
        $errores = 0;

        DB::beginTransaction();

        try {
            foreach ($repases as $repase) {
                try {
                    // Generar cantidad aleatoria de consultas (0-60)
                    $cantidadConsultas = rand(0, 60);

                    // Actualizar el repase
                    $repase->total_consultas = $cantidadConsultas;
                    $repase->save();

                    $actualizados++;
                } catch (\Exception $e) {
                    $errores++;
                    $this->newLine();
                    $this->error("Error al actualizar repase ID {$repase->id}: {$e->getMessage()}");
                }

                $progressBar->advance();
            }

            DB::commit();
            $progressBar->finish();

            $this->newLine(2);
            $this->info('=================================================');
            $this->info('  Actualización Completada');
            $this->info('=================================================');
            $this->newLine();

            // Resumen
            $this->info("✓ Repases actualizados exitosamente: {$actualizados}");
            
            if ($errores > 0) {
                $this->warn("✗ Repases con errores: {$errores}");
            }

            $this->newLine();

            // Mostrar estadísticas
            $this->info('Estadísticas de consultas actualizadas:');
            $stats = Repase::selectRaw('
                MIN(total_consultas) as minimo,
                MAX(total_consultas) as maximo,
                AVG(total_consultas) as promedio,
                SUM(total_consultas) as total
            ')->first();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Mínimo', number_format($stats->minimo, 0)],
                    ['Máximo', number_format($stats->maximo, 0)],
                    ['Promedio', number_format($stats->promedio, 2)],
                    ['Total', number_format($stats->total, 0)],
                ]
            );

            $this->newLine();
            $this->info('¡Proceso completado exitosamente!');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();

            $this->newLine(2);
            $this->error('=================================================');
            $this->error('  Error en la Actualización');
            $this->error('=================================================');
            $this->newLine();
            $this->error("Error: {$e->getMessage()}");
            $this->newLine();
            $this->warn('Se ha revertido la transacción. No se realizaron cambios.');

            return 1;
        }
    }
}
