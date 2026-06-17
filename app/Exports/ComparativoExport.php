<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Export de Reporte Comparativo a Excel
 */
class ComparativoExport implements WithMultipleSheets
{
    protected array $datos;
    protected array $filtros;

    public function __construct(array $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function sheets(): array
    {
        return [
            new ComparativoSheet($this->datos),
            new FiltrosSheet($this->filtros),
        ];
    }
}

/**
 * Hoja principal con comparación de períodos
 */
class ComparativoSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        $periodoActual = $this->datos['periodo_actual'];
        $periodoAnterior = $this->datos['periodo_anterior'];
        $variaciones = $this->datos['variaciones'];

        return [
            [
                'Total Ingresos',
                (float) $periodoAnterior['total_ingresos'],
                (float) $periodoActual['total_ingresos'],
                $variaciones['ingresos_variacion'] !== null ? (float) $variaciones['ingresos_variacion'] : 'N/A',
            ],
            [
                'Total Gastos',
                (float) $periodoAnterior['total_gastos'],
                (float) $periodoActual['total_gastos'],
                $variaciones['gastos_variacion'] !== null ? (float) $variaciones['gastos_variacion'] : 'N/A',
            ],
            [
                'Ganancia Neta',
                (float) $periodoAnterior['ganancia_neta'],
                (float) $periodoActual['ganancia_neta'],
                $variaciones['ganancia_variacion'] !== null ? (float) $variaciones['ganancia_variacion'] : 'N/A',
            ],
        ];
    }

    public function headings(): array
    {
        $periodoActual = $this->datos['periodo_actual'];
        $periodoAnterior = $this->datos['periodo_anterior'];

        return [
            'Métrica',
            'Período Anterior (' . date('d/m/Y', strtotime($periodoAnterior['fecha_inicio'])) . ' - ' . 
                date('d/m/Y', strtotime($periodoAnterior['fecha_fin'])) . ')',
            'Período Actual (' . date('d/m/Y', strtotime($periodoActual['fecha_inicio'])) . ' - ' . 
                date('d/m/Y', strtotime($periodoActual['fecha_fin'])) . ')',
            'Variación (%)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar formato de moneda a columnas B y C
        $sheet->getStyle('B2:C4')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

        // Aplicar formato de porcentaje a columna D
        $sheet->getStyle('D2:D4')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        // Estilo de encabezados
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        // Ajustar ancho de columnas
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function title(): string
    {
        return 'Comparativo';
    }
}
