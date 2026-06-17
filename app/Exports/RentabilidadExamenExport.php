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
 * Export de Rentabilidad por Examen a Excel
 */
class RentabilidadExamenExport implements WithMultipleSheets
{
    protected Collection $datos;
    protected array $filtros;

    public function __construct(Collection $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function sheets(): array
    {
        return [
            new RentabilidadExamenSheet($this->datos),
            new FiltrosSheet($this->filtros),
        ];
    }
}

/**
 * Hoja principal con datos de rentabilidad por examen
 */
class RentabilidadExamenSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'Examen',
            'Cantidad Total',
            'Total Ingresos',
            'Ingreso Promedio',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nombre_examen,
            (int) $row->cantidad_total,
            (float) $row->total_ingresos,
            $row->ingreso_promedio !== null ? (float) $row->ingreso_promedio : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar formato de moneda a columnas C y D (ingresos)
        $sheet->getStyle('C2:D' . ($this->datos->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

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
        return 'Rentabilidad por Examen';
    }
}
