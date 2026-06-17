<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DetalleRepasesExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    protected Collection $datos;
    protected array $filtros;

    protected array $rows;

    public function __construct(Collection $datos, array $filtros)
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
        $this->rows = $this->buildRows();
    }

    protected function buildRows(): array
    {
        $rows = [];

        foreach ($this->datos as $repase) {
            $fecha = $repase->fecha instanceof \Carbon\Carbon
                ? $repase->fecha->format('d/m/Y')
                : $repase->fecha;

            $rows[] = [
                $fecha,
                $repase->clinica->nombre ?? 'Sin clínica',
                $repase->id,
                ucfirst($repase->estado),
                (float) $repase->total_examenes,
                (float) $repase->total_gastos,
                (float) $repase->total_neto,
                'REPASE',
                '',
                '',
            ];

            if ($repase->gastos && $repase->gastos->isNotEmpty()) {
                foreach ($repase->gastos as $gasto) {
                    $rows[] = [
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $gasto->tipo,
                        $gasto->descripcion ?? '',
                        (float) $gasto->monto,
                    ];
                }
            } else {
                $rows[] = [
                    '', '', '', '', '', '', '',
                    '',
                    'Sin gastos registrados',
                    0,
                ];
            }

            $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Clínica',
            'ID',
            'Estado',
            'Ingresos',
            'Gastos',
            'Neto',
            'Tipo Gasto',
            'Descripción',
            'Monto Gasto',
        ];
    }

    public function title(): string
    {
        return 'Detalle de Repases';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = 'J';

                $this->styleHeader($sheet, $highestCol);
                $this->styleDataRows($sheet, $highestRow, $highestCol);
                $this->setColumnWidths($sheet);
            },
        ];
    }

    protected function styleHeader($sheet, string $highestCol): void
    {
        $range = "A1:{$highestCol}1";
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A365D'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    protected function styleDataRows($sheet, int $highestRow, string $highestCol): void
    {
        $currencyFormat = '#,##0.00';
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];

        for ($row = 2; $row <= $highestRow; $row++) {
            $cellH = $sheet->getCell("H{$row}")->getValue();
            $cellA = $sheet->getCell("A{$row}")->getValue();
            $isEmptySeparator = empty($cellA) && $cellH === null;
            $isRepaseRow = $cellH === 'REPASE';
            $isGastoRow = !$isRepaseRow && !$isEmptySeparator && !empty($cellH);

            if ($isEmptySeparator) {
                $sheet->getRowDimension($row)->setRowHeight(6);
                continue;
            }

            $rowRange = "A{$row}:{$highestCol}{$row}";

            if ($isRepaseRow) {
                $sheet->getStyle($rowRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => '1E293B'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle($rowRange)->applyFromArray($thinBorder);
                $sheet->getRowDimension($row)->setRowHeight(20);

                $sheet->getStyle("E{$row}:G{$row}")
                    ->getNumberFormat()
                    ->setFormatCode($currencyFormat);

                $sheet->getStyle("H{$row}")
                    ->getFont()
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1D4ED8'));

            } elseif ($isGastoRow) {
                $rowIndex = $row % 2;
                $bgColor = $rowIndex === 0 ? 'FFFFFF' : 'F8FAFC';

                $sheet->getStyle($rowRange)->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '475569'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle($rowRange)->applyFromArray($thinBorder);
                $sheet->getRowDimension($row)->setRowHeight(18);

                $sheet->getStyle("J{$row}")
                    ->getNumberFormat()
                    ->setFormatCode($currencyFormat);

                $sheet->getStyle("I{$row}")
                    ->getFont()
                    ->setItalic(true);

            } else {
                $sheet->getStyle($rowRange)->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '94A3B8'],
                        'italic' => true,
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }

            $sheet->getStyle("A{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle("{$highestCol}{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    protected function setColumnWidths($sheet): void
    {
        $widths = [
            'A' => 13,
            'B' => 20,
            'C' => 6,
            'D' => 12,
            'E' => 14,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 30,
            'J' => 14,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}
