<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Export de Rentabilidad por Clínica a Excel
 * 
 * Exporta datos de rentabilidad por clínica con formato apropiado:
 * - Valores monetarios con formato de moneda
 * - Porcentajes con formato de porcentaje
 * - Hoja de resumen con filtros aplicados
 */
class RentabilidadClinicaExport implements WithMultipleSheets
{
    protected Collection $datos;
    protected array $filtros;

    public function __construct(Collection $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    /**
     * Retorna las hojas del archivo Excel
     */
    public function sheets(): array
    {
        return [
            new RentabilidadClinicaSheet($this->datos),
            new FiltrosSheet($this->filtros),
        ];
    }
}

/**
 * Hoja principal con datos de rentabilidad por clínica
 */
class RentabilidadClinicaSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $datos;

    public function __construct(Collection $datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        return $this->datos;
    }

    public function headings(): array
    {
        return [
            'Clínica',
            'Total Ingresos',
            'Total Gastos',
            'Ganancia Neta',
            'Margen de Ganancia (%)',
            'Cantidad de Repases',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nombre_clinica,
            (float) $row->total_ingresos,
            (float) $row->total_gastos,
            (float) $row->ganancia_neta,
            $row->margen_ganancia !== null ? (float) $row->margen_ganancia : 'N/A',
            (int) $row->cantidad_repases,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar formato de moneda a columnas B, C, D (ingresos, gastos, ganancia)
        $sheet->getStyle('B2:D' . ($this->datos->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

        // Aplicar formato de porcentaje a columna E (margen)
        $sheet->getStyle('E2:E' . ($this->datos->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        // Estilo de encabezados
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        // Ajustar ancho de columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function title(): string
    {
        return 'Rentabilidad por Clínica';
    }
}
