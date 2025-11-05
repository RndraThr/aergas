<?php

namespace App\Exports;

use App\Models\GasInData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;

class GasInExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = GasInData::with([
            'calonPelanggan',
            'srData:reff_id_pelanggan,no_seri_mgrt,merk_brand_mgrt',
            'photoApprovals' => function($q) {
                $q->whereIn('photo_field_name', ['ba_gas_in', 'foto_bubble_test', 'foto_regulator', 'foto_kompor_menyala'])
                  ->whereNotNull('photo_url');
            },
            'cgpApprovedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);

        // Filter by tanggal_gas_in
        if (isset($this->filters['tanggal_dari']) && $this->filters['tanggal_dari']) {
            $query->whereDate('tanggal_gas_in', '>=', $this->filters['tanggal_dari']);
        }

        if (isset($this->filters['tanggal_sampai']) && $this->filters['tanggal_sampai']) {
            $query->whereDate('tanggal_gas_in', '<=', $this->filters['tanggal_sampai']);
        }

        // Filter by module_status
        if (isset($this->filters['module_status']) && $this->filters['module_status']) {
            $query->where('module_status', $this->filters['module_status']);
        }

        // Filter by search (nama, reff_id, alamat, telepon)
        if (isset($this->filters['search']) && $this->filters['search']) {
            $search = $this->filters['search'];
            $query->whereHas('calonPelanggan', function($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhere('no_telepon', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('tanggal_gas_in', 'desc')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Reff ID Pelanggan',
            'Nama Pelanggan',
            'Alamat',
            'Kelurahan',
            'Padukuhan',
            'No. Telepon',
            'Email',
            'Jenis Pelanggan',
            'Merk MGRT',
            'Nomor Seri MGRT',
            'Tanggal Gas In',
            'Overall Photo Status',
            'CGP Approved At',
            'CGP Approved By',
            'Link Foto BA Gas In',
            'Link Foto Bubble Test',
            'Link Foto Regulator (MGRT)',
            'Link Foto Kompor Menyala',
            'Created By',
            'Created At',
            'Updated By',
            'Updated At',
        ];
    }

    /**
     * @param mixed $gasIn
     * @return array
     */
    public function map($gasIn): array
    {
        // Ambil foto URLs
        $photos = $gasIn->photoApprovals->keyBy('photo_field_name');

        // Format Reff ID dengan prefix "00" agar menjadi 8 digit
        $reffId = $gasIn->reff_id_pelanggan;
        $formattedReffId = '00' . $reffId; // Tambah prefix 00 di depan

        return [
            $formattedReffId,
            $gasIn->calonPelanggan->nama_pelanggan ?? '-',
            $gasIn->calonPelanggan->alamat ?? '-',
            $gasIn->calonPelanggan->kelurahan ?? '-',
            $gasIn->calonPelanggan->padukuhan ?? '-',
            $gasIn->calonPelanggan->no_telepon ?? '-',
            $gasIn->calonPelanggan->email ?? '-',
            $gasIn->calonPelanggan->jenis_pelanggan ?? '-',
            $gasIn->srData->merk_brand_mgrt ?? '-',
            $gasIn->srData->no_seri_mgrt ?? '-',
            $gasIn->tanggal_gas_in ? $gasIn->tanggal_gas_in->format('d-m-Y') : '-',
            $this->getPhotoStatusLabel($gasIn->overall_photo_status),
            $gasIn->cgp_approved_at ? $gasIn->cgp_approved_at->format('d-m-Y H:i') : '-',
            $gasIn->cgpApprovedBy->name ?? '-',
            $photos->get('ba_gas_in')->photo_url ?? '-',
            $photos->get('foto_bubble_test')->photo_url ?? '-',
            $photos->get('foto_regulator')->photo_url ?? '-',
            $photos->get('foto_kompor_menyala')->photo_url ?? '-',
            $gasIn->createdBy->name ?? '-',
            $gasIn->created_at ? $gasIn->created_at->format('d-m-Y H:i') : '-',
            $gasIn->updatedBy->name ?? '-',
            $gasIn->updated_at ? $gasIn->updated_at->format('d-m-Y H:i') : '-',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Register events untuk freeze pane dan hyperlinks
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Freeze first column (Reff ID) dan header row
                $sheet->freezePane('B2'); // Freeze kolom A dan row 1

                // 2. Get highest row dengan data
                $highestRow = $sheet->getHighestRow();

                // 2a. Format kolom A (Reff ID) sebagai TEXT agar prefix "00" tidak hilang
                $sheet->getStyle('A2:A' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

                // 3. Convert URL foto menjadi hyperlink (kolom O, P, Q, R = kolom 15, 16, 17, 18)
                // Kolom O = Link Foto BA Gas In
                // Kolom P = Link Foto Bubble Test
                // Kolom Q = Link Foto Regulator
                // Kolom R = Link Foto Kompor Menyala

                for ($row = 2; $row <= $highestRow; $row++) {
                    // BA Gas In (kolom O)
                    $this->createHyperlink($sheet, 'O' . $row);

                    // Bubble Test (kolom P)
                    $this->createHyperlink($sheet, 'P' . $row);

                    // Regulator (kolom Q)
                    $this->createHyperlink($sheet, 'Q' . $row);

                    // Kompor Menyala (kolom R)
                    $this->createHyperlink($sheet, 'R' . $row);
                }

                // 4. Style untuk link columns (biru dan underline)
                $sheet->getStyle('O2:R' . $highestRow)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0563C1'],
                        'underline' => true,
                    ],
                ]);
            },
        ];
    }

    /**
     * Helper: Create hyperlink dari cell value
     */
    private function createHyperlink($sheet, $cell)
    {
        $cellValue = $sheet->getCell($cell)->getValue();

        // Jika bukan '-' dan adalah URL valid
        if ($cellValue && $cellValue !== '-' && filter_var($cellValue, FILTER_VALIDATE_URL)) {
            $sheet->getCell($cell)->getHyperlink()->setUrl($cellValue);
            $sheet->getCell($cell)->setValue('Lihat Foto'); // Ganti text dengan label
        }
    }

    /**
     * Get human-readable photo status label
     */
    private function getPhotoStatusLabel($status)
    {
        $labels = [
            'all_approved' => 'Semua Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
            'partial' => 'Sebagian Disetujui',
        ];

        return $labels[$status] ?? $status ?? '-';
    }
}
