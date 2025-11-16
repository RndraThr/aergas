<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class PilotComparisonExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $comparisons;

    public function __construct($comparisons)
    {
        $this->comparisons = $comparisons;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->comparisons;
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Reff ID Pelanggan',
            'Nama Pelanggan',
            'Alamat',
            'Status Comparison',
            'PILOT - Tanggal SK',
            'DB - Tanggal SK',
            'PILOT - Tanggal SR',
            'DB - Tanggal SR',
            'PILOT - Tanggal GAS IN',
            'DB - Tanggal GAS IN',
            'PILOT - Status SK',
            'DB - Status SK',
            'PILOT - Status SR',
            'DB - Status SR',
            'PILOT - Status GAS IN',
            'DB - Status GAS IN',
            'Perbedaan',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($comparison): array
    {
        $differences = '';
        if (!empty($comparison->differences) && is_array($comparison->differences)) {
            $diffs = [];
            foreach ($comparison->differences as $field => $diff) {
                if (isset($diff['pilot']) && isset($diff['db'])) {
                    $diffs[] = ucfirst(str_replace('_', ' ', $field)) . ': ' . $diff['pilot'] . ' → ' . $diff['db'];
                }
            }
            $differences = implode('; ', $diffs);
        }

        return [
            $comparison->reff_id_pelanggan,
            $comparison->nama_pelanggan ?? '-',
            $comparison->alamat ?? '-',
            $comparison->getStatusLabel(),
            $comparison->pilot_tanggal_sk ? \Carbon\Carbon::parse($comparison->pilot_tanggal_sk)->format('d/m/Y') : '-',
            $comparison->db_tanggal_sk ? \Carbon\Carbon::parse($comparison->db_tanggal_sk)->format('d/m/Y') : '-',
            $comparison->pilot_tanggal_sr ? \Carbon\Carbon::parse($comparison->pilot_tanggal_sr)->format('d/m/Y') : '-',
            $comparison->db_tanggal_sr ? \Carbon\Carbon::parse($comparison->db_tanggal_sr)->format('d/m/Y') : '-',
            $comparison->pilot_tanggal_gas_in ? \Carbon\Carbon::parse($comparison->pilot_tanggal_gas_in)->format('d/m/Y') : '-',
            $comparison->db_tanggal_gas_in ? \Carbon\Carbon::parse($comparison->db_tanggal_gas_in)->format('d/m/Y') : '-',
            $comparison->pilot_status_sk ?? '-',
            $comparison->db_status_sk ?? '-',
            $comparison->pilot_status_sr ?? '-',
            $comparison->db_status_sr ?? '-',
            $comparison->pilot_status_gas_in ?? '-',
            $comparison->db_status_gas_in ?? '-',
            $differences,
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Reff ID
            'B' => 25,  // Nama Pelanggan
            'C' => 35,  // Alamat
            'D' => 20,  // Status Comparison
            'E' => 15,  // PILOT Tanggal SK
            'F' => 15,  // DB Tanggal SK
            'G' => 15,  // PILOT Tanggal SR
            'H' => 15,  // DB Tanggal SR
            'I' => 15,  // PILOT Tanggal GAS IN
            'J' => 15,  // DB Tanggal GAS IN
            'K' => 15,  // PILOT Status SK
            'L' => 15,  // DB Status SK
            'M' => 15,  // PILOT Status SR
            'N' => 15,  // DB Status SR
            'O' => 18,  // PILOT Status GAS IN
            'P' => 18,  // DB Status GAS IN
            'Q' => 50,  // Perbedaan
        ];
    }
}
