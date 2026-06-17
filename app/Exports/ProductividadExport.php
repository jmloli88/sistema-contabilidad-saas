<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export de Productividad a Excel
 */
class ProductividadExport implements WithMultipleSheets
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
            new ProductividadResumenSheet($this->datos),
            new ProductividadPorExamenSheet($this->datos['por_examen']),
            new ProductividadPorClinicaSheet($this->datos['por_clinica']),
            new FiltrosSheet($this->filtros),
        ];
    }
}

/**
 * Hoja de resumen con métricas generales
 */
class ProductividadResumenSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        return [
            ['Total Exámenes Realizados', $this->datos['total_examenes_realizados']],
            ['Exámenes por Día', $this->datos['examenes_por_dia']],
            ['Total Repases', $this->datos['total_repases']],
            ['Exámenes por Repase', $this->datos['examenes_por_repase']],
        ];
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
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
        return 'Resumen';
    }
}

/**
 * Hoja con desglose por tipo de examen
 */
class ProductividadPorExamenSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->datos as $item) {
            $rows[] = [
                $item->nombre_examen,
                (int) $item->cantidad_total,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['Examen', 'Cantidad Total'];
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
        return 'Por Tipo de Examen';
    }
}

/**
 * Hoja con desglose por clínica
 */
class ProductividadPorClinicaSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->datos as $item) {
            $rows[] = [
                $item->nombre_clinica,
                (int) $item->cantidad_total,
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['Clínica', 'Cantidad Total'];
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
        return 'Por Clínica';
    }
}
