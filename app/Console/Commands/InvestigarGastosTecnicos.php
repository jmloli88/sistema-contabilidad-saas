<?php

namespace App\Console\Commands;

use App\Models\Repase;
use Illuminate\Console\Command;

class InvestigarGastosTecnicos extends Command
{
    protected $signature = 'repases:investigar-gastos {repase_id}';
    protected $description = 'Investiga los gastos de un repase específico';

    public function handle()
    {
        $repaseId = $this->argument('repase_id');
        $repase = Repase::with('gastos')->find($repaseId);

        if (!$repase) {
            $this->error("Repase con ID {$repaseId} no encontrado.");
            return 1;
        }

        $this->info("Repase ID: {$repase->id}");
        $this->info("Clínica: {$repase->clinica->nombre}");
        $this->info("Fecha: {$repase->fecha->format('Y-m-d')}");
        $this->info("Total Gastos: R\${$repase->total_gastos}");
        $this->newLine();

        $this->info("Gastos registrados:");
        $this->table(
            ['ID', 'Tipo', 'Descripción', 'Monto'],
            $repase->gastos->map(function ($gasto) {
                return [
                    $gasto->id,
                    $gasto->tipo,
                    $gasto->descripcion ?? 'NULL',
                    'R$' . number_format($gasto->monto, 2),
                ];
            })
        );

        // Buscar gastos con descripción similar a "técnico"
        $gastosTecnicos = $repase->gastos->filter(function ($g) {
            return stripos($g->descripcion, 'técnico') !== false || 
                   stripos($g->descripcion, 'tecnico') !== false ||
                   $g->tipo === 'tecnico';
        });

        if ($gastosTecnicos->isNotEmpty()) {
            $this->newLine();
            $this->warn("Gastos técnicos encontrados:");
            $this->table(
                ['ID', 'Tipo', 'Descripción', 'Monto'],
                $gastosTecnicos->map(function ($gasto) {
                    return [
                        $gasto->id,
                        $gasto->tipo,
                        $gasto->descripcion ?? 'NULL',
                        'R$' . number_format($gasto->monto, 2),
                    ];
                })
            );
            
            $totalTecnicos = $gastosTecnicos->sum('monto');
            $this->info("Total gastos técnicos: R\$" . number_format($totalTecnicos, 2));
        }

        return 0;
    }
}
