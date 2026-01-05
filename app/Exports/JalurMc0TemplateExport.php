<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class JalurMc0TemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            // Example rows
            [63, 'GDK', '001', 125.50],
            [90, 'GDK', '002', 87.30],
            [180, 'KRG', '001', 210.00],
            [63, 'KRG', '002', 45.75],
        ];
    }

    public function headings(): array
    {
        return [
            'diameter',
            'cluster_code',
            'line_number',
            'mc_0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (header) as bold with background color
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // diameter
            'B' => 15,  // cluster_code
            'C' => 15,  // line_number
            'D' => 12,  // mc_0
        ];
    }
}
