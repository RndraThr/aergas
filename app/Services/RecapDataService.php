<?php

namespace App\Services;

use App\Models\CalonPelanggan;

class RecapDataService
{
    public function __construct()
    {
        // For aergas main, materials and evidence slots are hardcoded static properties
    }

    /**
     * Get headers for export/sync
     */
    public function getHeaders(): array
    {
        return [
            'No',
            // Customer Info (8 columns)
            'Reff ID',
            'Nama Pelanggan',
            'Alamat',
            'No Telepon',
            'Kelurahan',
            'Padukuhan',
            'Jenis Pelanggan',
            'Tgl Registrasi',

            // SK Data (15 columns + 5 photo links)
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
            'SK - Foto Pneumatic Start',
            'SK - Foto Pneumatic Finish',
            'SK - Foto Valve',
            'SK - Foto Isometrik Scan',
            'SK - Foto Berita Acara',

            // SR Data (24 columns + 6 photo links)
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
            'SR - Foto Pneumatic Start',
            'SR - Foto Pneumatic Finish',
            'SR - Foto Jenis Tapping',
            'SR - Foto MGRT',
            'SR - Foto Pondasi',
            'SR - Foto Isometrik Scan',

            // GasIn Data (3 columns + 4 photo links)
            'GasIn - Tgl Gas In',
            'GasIn - Status',
            'GasIn - Tgl CGP Approval',
            'GasIn - Foto BA Gas In',
            'GasIn - Foto Bubble Test',
            'GasIn - Foto Regulator',
            'GasIn - Foto Kompor Menyala'
        ];
    }

    /**
     * Map complete query results to array for export/sync
     */
    public function mapData($customers): array
    {
        $data = [];
        foreach ($customers as $customer) {
            // Note: The 'No' column will be populated by SystemToSheetSyncController automatically
            $data[] = $this->mapCustomerRow($customer);
        }
        return $data;
    }

    /**
     * Map single customer to row array
     */
    public function mapCustomerRow($customer, ?int $sheetRowIndex = null): array
    {
        $sk = $customer->skData;
        $sr = $customer->srData;
        $gasIn = $customer->gasInData;

        $jenisMap = [
            'pengembangan' => 'Pengembangan',
            'penetrasi' => 'Penetrasi',
            'on_the_spot_penetrasi' => 'On The Spot Penetrasi',
            'on_the_spot_pengembangan' => 'On The Spot Pengembangan'
        ];

        return [
            // Prepend single quote directly to prevent scientific notation in Google Sheets
            "'" . '00' . $customer->reff_id_pelanggan,
            $customer->nama_pelanggan,
            $customer->alamat,
            $customer->no_telepon,
            $customer->kelurahan,
            $customer->padukuhan,
            $jenisMap[$customer->jenis_pelanggan] ?? $customer->jenis_pelanggan,
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

            // SK Photos
            $this->getEvidenceHyperlink($sk, 'pneumatic_start'),
            $this->getEvidenceHyperlink($sk, 'pneumatic_finish'),
            $this->getEvidenceHyperlink($sk, 'valve'),
            $this->getEvidenceHyperlink($sk, 'isometrik_scan'),
            $this->getEvidenceHyperlink($sk, 'berita_acara'),

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

            // SR Photos
            $this->getEvidenceHyperlink($sr, 'pneumatic_start'),
            $this->getEvidenceHyperlink($sr, 'pneumatic_finish'),
            $this->getEvidenceHyperlink($sr, 'jenis_tapping'),
            $this->getEvidenceHyperlink($sr, 'mgrt'),
            $this->getEvidenceHyperlink($sr, 'pondasi'),
            $this->getEvidenceHyperlink($sr, 'isometrik_scan'),

            // GasIn Data
            $gasIn?->tanggal_gas_in?->format('Y-m-d'),
            $gasIn?->module_status ?? '-',
            $gasIn?->cgp_approved_at?->format('Y-m-d H:i'),

            // GasIn Photos
            $this->getEvidenceHyperlink($gasIn, 'ba_gas_in'),
            $this->getEvidenceHyperlink($gasIn, 'foto_bubble_test'),
            $this->getEvidenceHyperlink($gasIn, 'foto_regulator'),
            $this->getEvidenceHyperlink($gasIn, 'foto_kompor_menyala'),
        ];
    }

    private function getEvidenceHyperlink($moduleData, string $photoFieldName): string
    {
        if (!$moduleData || !$moduleData->photoApprovals) {
            return '';
        }

        $photo = $moduleData->photoApprovals->firstWhere('photo_field_name', $photoFieldName);

        if (!$photo || (!$photo->drive_file_id && !$photo->photo_url)) {
            return '';
        }

        // Return standard HYPERLINK formula
        if ($photo->drive_file_id) {
            $url = "https://drive.google.com/file/d/{$photo->drive_file_id}/view";
            return '=HYPERLINK("' . $url . '"; "Lihat Foto")';
        }

        $url = rtrim($photo->photo_url ?? '', '/');
        if (!empty($url)) {
            return '=HYPERLINK("' . $url . '"; "Lihat Foto")';
        }
        
        return '';
    }

    /**
     * Build the standard query for completed customers
     */
    public function buildQuery($filters = [])
    {
        $query = CalonPelanggan::with([
            'validatedBy:id,name',
            'skData.photoApprovals',
            'srData.photoApprovals',
            'gasInData.photoApprovals',
            'photoApprovals.tracerUser',
            'photoApprovals.cgpUser',
        ]);

        return $query->orderBy('tanggal_registrasi', 'desc');
    }
}
