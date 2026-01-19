<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class JalurLoweringTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            'diameter',
            'cluster_code',
            'line_number',
            'tanggal_jalur',
            'nama_jalan',
            'tipe_bongkaran',
            'lowering',
            'bongkaran',
            'kedalaman',
            'cassing_quantity',
            'marker_tape_quantity',
            'concrete_slab_quantity',
            'landasan_quantity',
            'mc_0',
            'mc_100',
            'keterangan',
        ];
    }

    public function array(): array
    {
        return [
            [
                '63',
                'GDK',
                '001',
                '2025-09-15',
                'Jl. Contoh Raya',
                'Manual Boring',
                '45.50',
                '45.50',
                '80',
                '',
                '',
                '',
                '100.00',
                '45.50',
                '',
                'Contoh data lowering',
            ],
            [
                '63',
                'GDK',
                '002',
                '2025-09-16',
                'Jl. Contoh Indah',
                'Open Cut',
                '30.00',
                '30.00',
                '100',
                '10.5',
                '50.0',
                '25',
                '150.00',
                '30.00',
                '10.5',
                'Contoh dengan aksesoris',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
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
            'D' => 15,  // tanggal_jalur
            'E' => 25,  // nama_jalan
            'F' => 18,  // tipe_bongkaran
            'G' => 12,  // lowering
            'H' => 12,  // bongkaran
            'I' => 12,  // kedalaman
            'J' => 18,  // cassing_quantity
            'K' => 20,  // marker_tape_quantity
            'L' => 22,  // concrete_slab_quantity
            'M' => 20,  // landasan_quantity
            'N' => 12,  // mc_0
            'O' => 12,  // mc_100
            'P' => 30,  // keterangan
        ];
    }
}
