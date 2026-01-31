<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Collection;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use App\Models\JalurTestPackage;

class JalurExportService
{
    /**
     * Generate spreadsheet from Jalur data
     *
     * @param \Illuminate\Support\Collection $lineNumbers
     * @return Spreadsheet
     */
    public function generateSpreadsheet(Collection $lineNumbers): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Summary (default sheet)
        $sheetSummary = $spreadsheet->getActiveSheet();
        $sheetSummary->setTitle('Summary Line Number');
        $this->generateSummarySheet($sheetSummary, $lineNumbers);

        // Sheet 2: Detailed Lowering
        $sheetLowering = $spreadsheet->createSheet();
        $sheetLowering->setTitle('Data Lowering');
        $this->generateLoweringSheet($sheetLowering, $lineNumbers);

        // Sheet 3: Detailed Joint
        $sheetJoint = $spreadsheet->createSheet();
        $sheetJoint->setTitle('Data Joint');
        $this->generateJointSheet($sheetJoint, $lineNumbers);

        // Sheet 4: Test Package (Commissioning)
        // Note: We need test packages related to these lines.
        // Since test packages relate to clusters and have items(lines), we can fetch them.
        $this->generateTestPackageSheet($spreadsheet->createSheet()->setTitle('Data Test Package'));

        $sheetSummary->setSelectedCells('A1'); // Reset selection
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // ... existing generateSummarySheet ...

    // ... existing generateLoweringSheet ...

    // ... existing generateJointSheet ...

    private function generateTestPackageSheet($sheet)
    {
        $headers = [
            'Test Package Code',
            'Cluster',
            'Status',
            'Lines Included',
            'Flushing Date',
            'Flushing Evidence',
            'Pneumatic Date',
            'Pneumatic Evidence',
            'Purging Date',
            'Purging Evidence',
            'Gas In Date',
            'Gas In Evidence',
            'Created At',
            'Last Update'
        ];

        $this->setHeaders($sheet, $headers);

        // Fetch all test packages with relations
        $packages = JalurTestPackage::with(['cluster', 'items.lineNumber'])->get();

        $row = 2;
        foreach ($packages as $pkg) {
            // Format line numbers list
            $linesList = $pkg->items->map(function ($item) {
                return $item->lineNumber->line_number ?? '-';
            })->join(', ');

            $data = [
                $pkg->test_package_code,
                $pkg->cluster->nama_cluster ?? '-',
                ucfirst($pkg->status),
                $linesList,
                $pkg->flushing_date ? $pkg->flushing_date->format('Y-m-d') : '-',
                $pkg->flushing_evidence_path ?? '-',
                $pkg->pneumatic_date ? $pkg->pneumatic_date->format('Y-m-d') : '-',
                $pkg->pneumatic_evidence_path ?? '-',
                $pkg->purging_date ? $pkg->purging_date->format('Y-m-d') : '-',
                $pkg->purging_evidence_path ?? '-',
                $pkg->gas_in_date ? $pkg->gas_in_date->format('Y-m-d') : '-',
                $pkg->gas_in_evidence_path ?? '-',
                $pkg->created_at->format('Y-m-d'),
                $pkg->updated_at->format('Y-m-d')
            ];

            // Check photo/link columns (indexes 5, 7, 9, 11)
            $this->fillRow($sheet, $row, $data, [5, 7, 9, 11]);
            $row++;
        }

        $this->applyStyles($sheet, $headers, $packages->count());
    }



    private function generateSummarySheet($sheet, $lineNumbers)
    {
        $headers = [
            'Line Number',
            'Cluster',
            'Cluster Code',
            'Diameter (mm)',
            'Nama Jalan',
            'Status Line',
            'Estimasi Panjang (m)',
            'Actual MC-100 (m)',
            'Total Penggelaran (m)',
            'Lowering Entries',
            'Joint Total',
            'Joint Completed',
            'Variance (m)',
            'Variance (%)',
            'Progress (%)',
            'Overall Status',
            'Last Update'
        ];

        $this->setHeaders($sheet, $headers);

        $row = 2;
        foreach ($lineNumbers as $line) {
            // Calculations (mirroring JalurController logic)
            $estimasi = $line->estimasi_panjang ?? 0;
            $actualMc100 = $line->actual_mc100 ?? 0;
            $totalPenggelaran = $line->total_penggelaran ?? 0;

            $variance = $actualMc100 > 0 ? ($actualMc100 - $estimasi) : null;
            $variancePercent = ($estimasi > 0 && $variance !== null) ? (($variance / $estimasi) * 100) : null;
            $progress = $estimasi > 0 ? (($totalPenggelaran / $estimasi) * 100) : 0;

            // Determine overall status
            $loweringAccCgp = $line->loweringData->where('status_laporan', 'acc_cgp')->count();
            $jointAccCgp = $line->jointData->where('status_laporan', 'acc_cgp')->count();
            $totalRecords = $line->loweringData->count() + $line->jointData->count();
            $totalAccCgp = $loweringAccCgp + $jointAccCgp;

            $overallStatus = 'Pending';
            if ($totalRecords > 0 && $totalAccCgp === $totalRecords) {
                $overallStatus = 'Completed';
            } elseif ($totalRecords > 0) {
                $overallStatus = 'In Progress';
            }

            $data = [
                $line->line_number,
                $line->cluster->nama_cluster ?? '-',
                $line->cluster->code_cluster ?? '-',
                $line->diameter,
                $line->nama_jalan,
                $line->status_label,
                $estimasi,
                $actualMc100 > 0 ? $actualMc100 : '-',
                $totalPenggelaran,
                $line->loweringData->count(),
                $line->jointData->count(),
                $jointAccCgp,
                $variance !== null ? round($variance, 2) : '-',
                $variancePercent !== null ? round($variancePercent, 2) . '%' : '-',
                round($progress, 2) . '%',
                $overallStatus,
                $line->updated_at->format('Y-m-d H:i')
            ];

            $this->fillRow($sheet, $row, $data);
            $row++;
        }

        $this->applyStyles($sheet, $headers, $lineNumbers->count());
    }

    private function generateLoweringSheet($sheet, $lineNumbers)
    {
        $headers = [
            'Line Number',
            'Cluster',
            'Tanggal Jalur',
            'Penggelaran (m)',
            'Kedalaman (cm)',
            'Tipe Bongkaran',
            'Tipe Material',
            'Status Laporan',
            'Tanggal ACC Tracer',
            'Tanggal ACC CGP',
            'Foto Evidence Penggelaran/Bongkaran',
            'Foto Evidence Kedalaman',
            'Foto Evidence Marker Tape',
            'Foto Evidence Concrete Slab',
            'Foto Evidence Cassing',
            'Foto Evidence Landasan'
        ];

        $this->setHeaders($sheet, $headers);

        $row = 2;
        foreach ($lineNumbers as $line) {
            foreach ($line->loweringData as $lowering) {
                $data = [
                    $line->line_number,
                    $line->cluster->nama_cluster ?? '-',
                    $lowering->tanggal_jalur ? $lowering->tanggal_jalur->format('Y-m-d') : '-',
                    $lowering->penggelaran,
                    $lowering->kedalaman_lowering,
                    $lowering->tipe_bongkaran,
                    $lowering->tipe_material,
                    $lowering->status_label,
                    $lowering->tracer_approved_at ? $lowering->tracer_approved_at->format('Y-m-d') : '-',
                    $lowering->cgp_approved_at ? $lowering->cgp_approved_at->format('Y-m-d') : '-',
                    // Photos
                    $this->getPhotoUrl($lowering, 'foto_evidence_penggelaran_bongkaran'),
                    $this->getPhotoUrl($lowering, 'foto_evidence_kedalaman_lowering'),
                    $this->getPhotoUrl($lowering, 'foto_evidence_marker_tape'),
                    $this->getPhotoUrl($lowering, 'foto_evidence_concrete_slab'),
                    $this->getPhotoUrl($lowering, 'foto_evidence_cassing'),
                    $this->getPhotoUrl($lowering, 'foto_evidence_landasan'),
                ];

                // Check photo columns (indexes 10-15)
                $this->fillRow($sheet, $row, $data, range(10, 15));
                $row++;
            }
        }

        $this->applyStyles($sheet, $headers, $row - 2);
    }

    private function generateJointSheet($sheet, $lineNumbers)
    {
        $headers = [
            'Line Number',
            'Cluster',
            'Nomor Joint',
            'Tanggal Joint',
            'Line From',
            'Line To',
            'Example (Optional)',
            'Fitting Type',
            'Tipe Penyambungan',
            'Lokasi',
            'Status Laporan',
            'Tanggal ACC Tracer',
            'Tanggal ACC CGP',
            'Foto Evidence Joint',
            'Foto Sebelum',
            'Foto Sesudah',
            'Foto Tambahan'
        ];

        $this->setHeaders($sheet, $headers);

        $row = 2;
        foreach ($lineNumbers as $line) {
            foreach ($line->jointData as $joint) {
                $data = [
                    $line->line_number,
                    $line->cluster->nama_cluster ?? '-',
                    $joint->nomor_joint,
                    $joint->tanggal_joint ? $joint->tanggal_joint->format('Y-m-d') : '-',
                    $joint->joint_line_from,
                    $joint->joint_line_to,
                    $joint->joint_line_optional ?? '-',
                    $joint->fittingType->nama_fitting ?? '-',
                    $joint->tipe_penyambungan,
                    $joint->lokasi_joint ?? '-',
                    $joint->status_label,
                    $joint->tracer_approved_at ? $joint->tracer_approved_at->format('Y-m-d') : '-',
                    $joint->cgp_approved_at ? $joint->cgp_approved_at->format('Y-m-d') : '-',
                    // Photos
                    $this->getPhotoUrl($joint, 'foto_evidence_joint'),
                    $this->getPhotoUrl($joint, 'foto_sebelum'),
                    $this->getPhotoUrl($joint, 'foto_sesudah'),
                    $this->getPhotoUrl($joint, 'foto_tambahan'),
                ];

                // Check photo columns (indexes 13-16)
                $this->fillRow($sheet, $row, $data, range(13, 16));
                $row++;
            }
        }

        $this->applyStyles($sheet, $headers, $row - 2);
    }



    private function setHeaders($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ];

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    private function fillRow($sheet, $row, array $data, array $photoColumns = [])
    {
        $col = 'A';
        foreach ($data as $index => $value) {
            $cellRef = $col . $row;

            // Check if this is a photo column with valid URL
            if (in_array($index, $photoColumns) && $value && $value !== '-' && str_starts_with($value, 'http')) {
                // Set hyperlink
                $sheet->setCellValue($cellRef, 'Lihat Foto');
                $sheet->getCell($cellRef)->getHyperlink()->setUrl($value);

                // Style hyperlink (blue, underlined)
                $sheet->getStyle($cellRef)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '0563C1'],
                        'underline' => \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE
                    ]
                ]);
            } else {
                $sheet->setCellValue($cellRef, $value ?? '');
            }
            $col++;
        }
    }

    private function applyStyles($sheet, array $headers, int $dataCount): void
    {
        if ($dataCount === 0)
            return;

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $lastDataRow = $dataCount + 1;

        // Borders
        $sheet->getStyle('A1:' . $lastCol . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ]
        ]);

        // Zebra striping
        for ($i = 2; $i <= $lastDataRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':' . $lastCol . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9FAFB']
                    ]
                ]);
            }
        }

        // Auto-size columns
        for ($i = 1; $i <= count($headers); $i++) {
            $columnID = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Freeze panes
        $sheet->freezePane('B2');
    }

    private function getPhotoUrl($moduleData, string $photoFieldName): string
    {
        if (!$moduleData || !$moduleData->photoApprovals) {
            return '-';
        }

        $photo = $moduleData->photoApprovals->firstWhere('photo_field_name', $photoFieldName);

        if (!$photo) {
            return '-';
        }

        // 1. If we have a direct drive_link, usage that (preferred for Drive files)
        if (!empty($photo->drive_link)) {
            // If it's already a view link, return it
            if (str_contains($photo->drive_link, 'drive.google.com') || str_contains($photo->drive_link, 'docs.google.com')) {
                return $photo->drive_link;
            }
        }

        // 2. If we have a photo_url
        if (!empty($photo->photo_url)) {
            // Check if it's a Google Drive URL
            if (str_contains($photo->photo_url, 'drive.google.com') || str_contains($photo->photo_url, 'docs.google.com')) {
                return $photo->photo_url;
            }

            // Check if it's a local storage path
            if (!str_contains($photo->photo_url, 'http')) {
                // Return full asset URL for local files
                // Assuming standard storage link: http://domain.com/storage/path/to/file.jpg
                return asset('storage/' . ltrim($photo->photo_url, '/'));
            }

            return $photo->photo_url;
        }

        // 3. Fallback: Check for legacy/magic drive_file_id (if it exists on model)
        if (!empty($photo->drive_file_id)) {
            return "https://drive.google.com/file/d/{$photo->drive_file_id}/view";
        }

        return '-';
    }
}
