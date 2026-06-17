<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AnalisisConsultasExport implements WithMultipleSheets
{
    protected $datos;
    protected $filtros;

    public function __construct(array $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function sheets(): array
    {
        return [
            new AnalisisConsultasResumenSheet($this->datos, $this->filtros),
            new AnalisisConsultasPorClinicaSheet($this->datos['por_clinica']),
            new AnalisisConsultasPorMesSheet($this->datos['por_mes']),
            new FiltrosSheet($this->filtros),
        ];
    }
}

class AnalisisConsultasResumenSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;
    protected $filtros;

    public function __construct(array $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function collection()
    {
        return collect([
            [
                'Total Consultas' => $this->datos['total_consultas'],
                'Consultas por Repase' => $this->datos['consultas_por_repase'],
                'Total Repases' => $this->datos['total_repases'],
                'Total Exámenes' => $this->datos['total_examenes'],
                'Ratio Exámenes/Consultas' => $this->datos['ratio_examenes_consultas'] ?? 'N/A',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Total Consultas',
            'Consultas por Repase',
            'Total Repases',
            'Total Exámenes',
            'Ratio Exámenes/Consultas',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return 'Resumen';
    }
}

class AnalisisConsultasPorClinicaSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        return collect($this->datos)->map(function ($clinica, $index) {
            return [
                'Ranking' => $index + 1,
                'Clínica' => $clinica->nombre_clinica,
                'Total Consultas' => $clinica->total_consultas,
                'Cantidad Repases' => $clinica->cantidad_repases,
                'Consultas por Repase' => $clinica->consultas_por_repase,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Ranking',
            'Clínica',
            'Total Consultas',
            'Cantidad Repases',
            'Consultas por Repase',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '14B8A6']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return 'Por Clínica';
    }
}

class AnalisisConsultasPorMesSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        return collect($this->datos)->map(function ($mes) {
            return [
                'Mes' => $mes->mes,
                'Total Consultas' => $mes->total_consultas,
                'Cantidad Repases' => $mes->cantidad_repases,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Mes',
            'Total Consultas',
            'Cantidad Repases',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return 'Evolución Mensual';
    }
}
