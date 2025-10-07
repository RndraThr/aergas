<?php

namespace App\Exports;

use App\Models\CalonPelanggan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ComprehensiveReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = CalonPelanggan::with([
            'skData',
            'skData.photoApprovals' => function($q) {
                $q->where('photo_status', 'cgp_approved')
                  ->whereNotNull('drive_file_id');
            },
            'srData',
            'srData.photoApprovals' => function($q) {
                $q->where('photo_status', 'cgp_approved')
                  ->whereNotNull('drive_file_id');
            },
            'gasInData',
            'gasInData.photoApprovals' => function($q) {
                $q->where('photo_status', 'cgp_approved')
                  ->whereNotNull('drive_file_id');
            }
        ])
        ->where('progress_status', 'done')
        ->whereHas('skData', function($q) {
            $q->where('module_status', 'completed');
        })
        ->whereHas('srData', function($q) {
            $q->where('module_status', 'completed');
        })
        ->whereHas('gasInData', function($q) {
            $q->where('module_status', 'completed');
        });

        // Apply filters
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhere('no_telepon', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['kelurahan'])) {
            $query->where('kelurahan', 'like', '%' . $this->filters['kelurahan'] . '%');
        }

        if (!empty($this->filters['padukuhan'])) {
            $query->where('padukuhan', 'like', '%' . $this->filters['padukuhan'] . '%');
        }

        if (!empty($this->filters['jenis_pelanggan'])) {
            $query->where('jenis_pelanggan', $this->filters['jenis_pelanggan']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_registrasi', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_registrasi', '<=', $this->filters['end_date']);
        }

        return $query->latest('tanggal_registrasi')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            // Customer Info (8 columns)
            'Reff ID',
            'Nama Pelanggan',
            'Alamat',
            'No Telepon',
            'Kelurahan',
            'Padukuhan',
            'Jenis Pelanggan',
            'Tgl Registrasi',

            // SK Data (15 columns)
            'SK - Tgl Instalasi',
            'SK - Pipa GL Medium (m)',
            'SK - Elbow 1/2"',
            'SK - SockDraft 1/2"',
            'SK - Ball Valve 1/2"',
            'SK - Nipel Selang 1/2"',
            'SK - Elbow Reduce 3/4"x1/2"',
            'SK - Long Elbow 3/4"',
            'SK - Klem Pipa 1/2"',
            'SK - Double Nipple 1/2"',
            'SK - Seal Tape',
            'SK - Tee 1/2"',
            'SK - Total Fitting (pcs)',
            'SK - Status',
            'SK - Tgl CGP Approval',

            // SR Data (21 columns)
            'SR - Tgl Pemasangan',
            'SR - Tapping Saddle',
            'SR - Coupler 20mm',
            'SR - Pipa PE 20mm (m)',
            'SR - Elbow 90x20',
            'SR - Transition Fitting',
            'SR - Pondasi Tiang (m)',
            'SR - Pipa Galvanize 3/4" (m)',
            'SR - Klem Pipa',
            'SR - Ball Valve 3/4"',
            'SR - Double Nipple 3/4"',
            'SR - Long Elbow 3/4"',
            'SR - Regulator Service',
            'SR - Coupling MGRT',
            'SR - MGRT',
            'SR - Casing 1" (m)',
            'SR - Sealtape',
            'SR - Jenis Tapping',
            'SR - No Seri MGRT',
            'SR - Merk MGRT',
            'SR - Total Items (pcs)',
            'SR - Total Lengths (m)',
            'SR - Status',
            'SR - Tgl CGP Approval',

            // Gas In Data (3 columns)
            'GasIn - Tgl Gas In',
            'GasIn - Status',
            'GasIn - Tgl CGP Approval',
        ];
    }

    /**
     * @param mixed $customer
     * @return array
     */
    public function map($customer): array
    {
        $sk = $customer->skData;
        $sr = $customer->srData;
        $gasIn = $customer->gasInData;

        return [
            // Customer Info
            $customer->reff_id_pelanggan,
            $customer->nama_pelanggan,
            $customer->alamat,
            $customer->no_telepon,
            $customer->kelurahan,
            $customer->padukuhan,
            $this->formatJenisPelanggan($customer->jenis_pelanggan),
            $customer->tanggal_registrasi?->format('Y-m-d'),

            // SK Data
            $sk?->tanggal_instalasi?->format('Y-m-d'),
            $sk?->panjang_pipa_gl_medium_m ?? 0,
            $sk?->qty_elbow_1_2_galvanis ?? 0,
            $sk?->qty_sockdraft_galvanis_1_2 ?? 0,
            $sk?->qty_ball_valve_1_2 ?? 0,
            $sk?->qty_nipel_selang_1_2 ?? 0,
            $sk?->qty_elbow_reduce_3_4_1_2 ?? 0,
            $sk?->qty_long_elbow_3_4_male_female ?? 0,
            $sk?->qty_klem_pipa_1_2 ?? 0,
            $sk?->qty_double_nipple_1_2 ?? 0,
            $sk?->qty_seal_tape ?? 0,
            $sk?->qty_tee_1_2 ?? 0,
            $sk?->getTotalFittingQty() ?? 0,
            $sk?->module_status ?? '-',
            $sk?->cgp_approved_at?->format('Y-m-d H:i'),

            // SR Data
            $sr?->tanggal_pemasangan?->format('Y-m-d'),
            $sr?->qty_tapping_saddle ?? 0,
            $sr?->qty_coupler_20mm ?? 0,
            $sr?->panjang_pipa_pe_20mm_m ?? 0,
            $sr?->qty_elbow_90x20 ?? 0,
            $sr?->qty_transition_fitting ?? 0,
            $sr?->panjang_pondasi_tiang_sr_m ?? 0,
            $sr?->panjang_pipa_galvanize_3_4_m ?? 0,
            $sr?->qty_klem_pipa ?? 0,
            $sr?->qty_ball_valve_3_4 ?? 0,
            $sr?->qty_double_nipple_3_4 ?? 0,
            $sr?->qty_long_elbow_3_4 ?? 0,
            $sr?->qty_regulator_service ?? 0,
            $sr?->qty_coupling_mgrt ?? 0,
            $sr?->qty_meter_gas_rumah_tangga ?? 0,
            $sr?->panjang_casing_1_inch_m ?? 0,
            $sr?->qty_sealtape ?? 0,
            $sr?->jenis_tapping ?? '-',
            $sr?->no_seri_mgrt ?? '-',
            $sr?->merk_brand_mgrt ?? '-',
            $sr?->getTotalItemQty() ?? 0,
            $sr?->getTotalLengthsQty() ?? 0,
            $sr?->module_status ?? '-',
            $sr?->cgp_approved_at?->format('Y-m-d H:i'),

            // Gas In Data
            $gasIn?->tanggal_gas_in?->format('Y-m-d'),
            $gasIn?->module_status ?? '-',
            $gasIn?->cgp_approved_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A8A'], // Dark blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Format jenis pelanggan untuk display
     */
    private function formatJenisPelanggan($jenis): string
    {
        $jenisMap = [
            'pengembangan' => 'Pengembangan',
            'penetrasi' => 'Penetrasi',
            'on_the_spot_penetrasi' => 'On The Spot Penetrasi',
            'on_the_spot_pengembangan' => 'On The Spot Pengembangan'
        ];

        return $jenisMap[$jenis] ?? $jenis;
    }
}
