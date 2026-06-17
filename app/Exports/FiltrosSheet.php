<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de resumen con filtros aplicados
 * 
 * Esta hoja se incluye en todas las exportaciones para documentar
 * los filtros que se aplicaron al generar el reporte.
 */
class FiltrosSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $filtros;

    public function __construct(array $filtros)
    {
        $this->filtros = $filtros;
    }

    public function array(): array
    {
        $rows = [];

        if (isset($this->filtros['fecha_inicio']) && isset($this->filtros['fecha_fin'])) {
            $rows[] = [
                'Período',
                date('d/m/Y', strtotime($this->filtros['fecha_inicio'])) . ' - ' . 
                date('d/m/Y', strtotime($this->filtros['fecha_fin'])),
            ];
        }

        if (isset($this->filtros['clinica_nombre'])) {
            $rows[] = ['Clínica', $this->filtros['clinica_nombre']];
        }

        if (isset($this->filtros['examen_nombre'])) {
            $rows[] = ['Examen', $this->filtros['examen_nombre']];
        }

        if (empty($rows)) {
            $rows[] = ['Sin filtros aplicados', ''];
        }

        // Agregar fecha de generación
        $rows[] = ['Fecha de Generación', now()->format('d/m/Y H:i:s')];

        return $rows;
    }

    public function headings(): array
    {
        return ['Filtro', 'Valor'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function title(): string
    {
        return 'Filtros Aplicados';
    }
}
