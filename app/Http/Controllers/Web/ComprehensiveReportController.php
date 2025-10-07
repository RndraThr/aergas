<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CalonPelanggan;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use App\Models\PhotoApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprehensiveReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get filters
            $filters = [
                'search' => $request->input('search'),
                'kelurahan' => $request->input('kelurahan'),
                'padukuhan' => $request->input('padukuhan'),
                'jenis_pelanggan' => $request->input('jenis_pelanggan'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // CRITICAL FIX: Clean "null" string to actual null
            foreach ($filters as $key => $value) {
                if ($value === 'null' || $value === '' || $value === null) {
                    $filters[$key] = null;
                }
            }

            // Base query - Only completed customers
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
            if ($filters['search']) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                      ->orWhere('alamat', 'like', "%{$search}%")
                      ->orWhere('no_telepon', 'like', "%{$search}%");
                });
            }

            if ($filters['kelurahan']) {
                $query->where('kelurahan', 'like', '%' . $filters['kelurahan'] . '%');
            }

            if ($filters['padukuhan']) {
                $query->where('padukuhan', 'like', '%' . $filters['padukuhan'] . '%');
            }

            if ($filters['jenis_pelanggan']) {
                $query->where('jenis_pelanggan', $filters['jenis_pelanggan']);
            }

            if ($filters['start_date']) {
                $query->whereDate('tanggal_registrasi', '>=', $filters['start_date']);
            }

            if ($filters['end_date']) {
                $query->whereDate('tanggal_registrasi', '<=', $filters['end_date']);
            }

            // Get results with pagination
            $perPage = min(max((int) $request->input('per_page', 25), 10), 100);
            $customers = $query->latest('tanggal_registrasi')->paginate($perPage);

            // Get dropdown data for filters
            $kelurahanList = CalonPelanggan::whereNotNull('kelurahan')
                ->where('kelurahan', '!=', '')
                ->distinct()
                ->pluck('kelurahan')
                ->sort()
                ->values();

            $padukuhanList = CalonPelanggan::whereNotNull('padukuhan')
                ->where('padukuhan', '!=', '')
                ->distinct()
                ->pluck('padukuhan')
                ->sort()
                ->values();

            // Statistics
            $stats = [
                'total_completed' => $query->count(),
                'total_customers' => CalonPelanggan::count(),
                'completion_rate' => CalonPelanggan::count() > 0
                    ? round(($query->count() / CalonPelanggan::count()) * 100, 1)
                    : 0
            ];

            // For AJAX requests
            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json([
                    'success' => true,
                    'data' => $customers,
                    'stats' => $stats,
                    'filters' => $filters
                ]);
            }

            return view('reports.comprehensive', compact(
                'customers',
                'kelurahanList',
                'padukuhanList',
                'filters',
                'stats'
            ));

        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            return view('reports.comprehensive', [
                'customers' => collect()->paginate(),
                'kelurahanList' => collect(),
                'padukuhanList' => collect(),
                'filters' => $filters ?? [],
                'stats' => ['total_completed' => 0, 'total_customers' => 0, 'completion_rate' => 0]
            ]);
        }
    }

    public function export(Request $request)
    {
        try {
            $filters = $this->prepareFilters($request);
            $customers = $this->getCustomersForExport($filters);

            if ($customers->isEmpty()) {
                Log::warning('No customers found for export with current filters');
                return back()->with('error', 'Tidak ada data pelanggan yang sesuai filter');
            }

            $spreadsheet = $this->generateSpreadsheet($customers);
            $filename = 'Laporan_Lengkap_' . now()->format('Y-m-d_His') . '.xlsx';

            return $this->downloadSpreadsheet($spreadsheet, $filename);

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (config('app.debug')) {
                throw $e;
            }

            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    private function prepareFilters(Request $request): array
    {
        $filters = [
            'search' => $request->input('search'),
            'kelurahan' => $request->input('kelurahan'),
            'padukuhan' => $request->input('padukuhan'),
            'jenis_pelanggan' => $request->input('jenis_pelanggan'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        foreach ($filters as $key => $value) {
            if ($value === 'null' || $value === '' || $value === null) {
                $filters[$key] = null;
            }
        }

        Log::info('Export called with filters:', $filters);
        return $filters;
    }

    private function getCustomersForExport(array $filters)
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
        ->whereHas('skData', fn($q) => $q->where('module_status', 'completed'))
        ->whereHas('srData', fn($q) => $q->where('module_status', 'completed'))
        ->whereHas('gasInData', fn($q) => $q->where('module_status', 'completed'));

        $this->applyFilters($query, $filters);
        $customers = $query->latest('tanggal_registrasi')->get();

        Log::info('Export query results:', [
            'total_customers' => $customers->count(),
            'first_customer' => $customers->first()?->reff_id_pelanggan,
        ]);

        return $customers;
    }

    private function applyFilters($query, array $filters): void
    {
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhere('no_telepon', 'like', "%{$search}%");
            });
        }

        if ($filters['kelurahan']) {
            $query->where('kelurahan', 'like', '%' . $filters['kelurahan'] . '%');
        }

        if ($filters['padukuhan']) {
            $query->where('padukuhan', 'like', '%' . $filters['padukuhan'] . '%');
        }

        if ($filters['jenis_pelanggan']) {
            $query->where('jenis_pelanggan', $filters['jenis_pelanggan']);
        }

        if ($filters['start_date']) {
            $query->whereDate('tanggal_registrasi', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('tanggal_registrasi', '<=', $filters['end_date']);
        }
    }

    private function generateSpreadsheet($customers)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->getExportHeaders();
        $this->setHeaders($sheet, $headers);
        $this->fillData($sheet, $customers);
        $this->applyStyles($sheet, $headers, $customers->count());

        return $spreadsheet;
    }

    private function setHeaders($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ];

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(35);
    }

    private function fillData($sheet, $customers): void
    {
        // Photo link column indexes (0-based)
        // SK Photos: columns 23-27 (after 8 customer + 15 SK data)
        // SR Photos: columns 52-57 (after 8 customer + 15 SK + 5 SK photos + 24 SR data)
        // GasIn Photos: columns 61-64 (after all above + 3 GasIn data)
        $photoColumns = [
            23, 24, 25, 26, 27,  // SK photos
            52, 53, 54, 55, 56, 57,  // SR photos
            61, 62, 63, 64  // GasIn photos
        ];

        $row = 2;
        foreach ($customers as $customer) {
            $data = $this->mapCustomerData($customer);
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
            $row++;
        }

        Log::info('Excel data filled', ['total_rows' => $row - 2]);
    }

    private function applyStyles($sheet, array $headers, int $dataCount): void
    {
        if ($dataCount === 0) return;

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $lastDataRow = $dataCount + 1;

        // Borders
        $sheet->getStyle('A1:' . $lastCol . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ]
        ]);

        // Zebra striping
        for ($i = 2; $i <= $lastDataRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':' . $lastCol . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9FAFB']
                    ]
                ]);
            }
        }

        // Auto-size columns
        for ($i = 1; $i <= count($headers); $i++) {
            $columnID = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Freeze panes
        $sheet->freezePane('B2');
        $sheet->setSelectedCells('A1');
    }

    private function downloadSpreadsheet($spreadsheet, string $filename)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        if (!file_exists($temp_file) || filesize($temp_file) === 0) {
            throw new \Exception('Failed to generate Excel file');
        }

        Log::info('Export file ready for download', ['filename' => $filename]);

        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ])->deleteFileAfterSend(true);
    }

    private function getExportHeaders(): array
    {
        return [
            // Customer Info (8 columns)
            'Reff ID', 'Nama Pelanggan', 'Alamat', 'No Telepon', 'Kelurahan', 'Padukuhan', 'Jenis Pelanggan', 'Tgl Registrasi',

            // SK Data (15 columns + 5 photo links)
            'SK - Tgl Instalasi', 'SK - Pipa GL Medium (m)', 'SK - Elbow 1/2"', 'SK - SockDraft 1/2"', 'SK - Ball Valve 1/2"',
            'SK - Nipel Selang 1/2"', 'SK - Elbow Reduce 3/4"x1/2"', 'SK - Long Elbow 3/4"', 'SK - Klem Pipa 1/2"',
            'SK - Double Nipple 1/2"', 'SK - Seal Tape', 'SK - Tee 1/2"', 'SK - Total Fitting (pcs)', 'SK - Status', 'SK - Tgl CGP Approval',
            'SK - Foto Pneumatic Start', 'SK - Foto Pneumatic Finish', 'SK - Foto Valve', 'SK - Foto Isometrik Scan', 'SK - Foto Berita Acara',

            // SR Data (24 columns + 6 photo links)
            'SR - Tgl Pemasangan', 'SR - Tapping Saddle', 'SR - Coupler 20mm', 'SR - Pipa PE 20mm (m)', 'SR - Elbow 90x20',
            'SR - Transition Fitting', 'SR - Pondasi Tiang (m)', 'SR - Pipa Galvanize 3/4" (m)', 'SR - Klem Pipa',
            'SR - Ball Valve 3/4"', 'SR - Double Nipple 3/4"', 'SR - Long Elbow 3/4"', 'SR - Regulator Service',
            'SR - Coupling MGRT', 'SR - MGRT', 'SR - Casing 1" (m)', 'SR - Sealtape', 'SR - Jenis Tapping',
            'SR - No Seri MGRT', 'SR - Merk MGRT', 'SR - Total Items (pcs)', 'SR - Total Lengths (m)', 'SR - Status', 'SR - Tgl CGP Approval',
            'SR - Foto Pneumatic Start', 'SR - Foto Pneumatic Finish', 'SR - Foto Jenis Tapping', 'SR - Foto MGRT', 'SR - Foto Pondasi', 'SR - Foto Isometrik Scan',

            // GasIn Data (3 columns + 4 photo links)
            'GasIn - Tgl Gas In', 'GasIn - Status', 'GasIn - Tgl CGP Approval',
            'GasIn - Foto BA Gas In', 'GasIn - Foto Bubble Test', 'GasIn - Foto Regulator', 'GasIn - Foto Kompor Menyala'
        ];
    }

    private function mapCustomerData($customer): array
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
            // Customer Info
            $customer->reff_id_pelanggan,
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
            $this->getPhotoUrl($sk, 'pneumatic_start'),
            $this->getPhotoUrl($sk, 'pneumatic_finish'),
            $this->getPhotoUrl($sk, 'valve'),
            $this->getPhotoUrl($sk, 'isometrik_scan'),
            $this->getPhotoUrl($sk, 'berita_acara'),

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
            $this->getPhotoUrl($sr, 'pneumatic_start'),
            $this->getPhotoUrl($sr, 'pneumatic_finish'),
            $this->getPhotoUrl($sr, 'jenis_tapping'),
            $this->getPhotoUrl($sr, 'mgrt'),
            $this->getPhotoUrl($sr, 'pondasi'),
            $this->getPhotoUrl($sr, 'isometrik_scan'),

            // GasIn Data
            $gasIn?->tanggal_gas_in?->format('Y-m-d'),
            $gasIn?->module_status ?? '-',
            $gasIn?->cgp_approved_at?->format('Y-m-d H:i'),

            // GasIn Photos
            $this->getPhotoUrl($gasIn, 'ba_gas_in'),
            $this->getPhotoUrl($gasIn, 'foto_bubble_test'),
            $this->getPhotoUrl($gasIn, 'foto_regulator'),
            $this->getPhotoUrl($gasIn, 'foto_kompor_menyala'),
        ];
    }

    private function getPhotoUrl($moduleData, string $photoFieldName): string
    {
        if (!$moduleData || !$moduleData->photoApprovals) {
            return '-';
        }

        $photo = $moduleData->photoApprovals->firstWhere('photo_field_name', $photoFieldName);

        if (!$photo || !$photo->drive_file_id) {
            return '-';
        }

        return "https://drive.google.com/file/d/{$photo->drive_file_id}/view";
    }
}
