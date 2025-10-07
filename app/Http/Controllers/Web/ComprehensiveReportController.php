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
            $filters = [
                'search' => $request->input('search'),
                'kelurahan' => $request->input('kelurahan'),
                'padukuhan' => $request->input('padukuhan'),
                'jenis_pelanggan' => $request->input('jenis_pelanggan'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // Get data using same query as export class
            $query = CalonPelanggan::with([
                'skData',
                'srData',
                'gasInData'
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
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                      ->orWhere('alamat', 'like', "%{$search}%")
                      ->orWhere('no_telepon', 'like', "%{$search}%");
                });
            }

            if (!empty($filters['kelurahan'])) {
                $query->where('kelurahan', 'like', '%' . $filters['kelurahan'] . '%');
            }

            if (!empty($filters['padukuhan'])) {
                $query->where('padukuhan', 'like', '%' . $filters['padukuhan'] . '%');
            }

            if (!empty($filters['jenis_pelanggan'])) {
                $query->where('jenis_pelanggan', $filters['jenis_pelanggan']);
            }

            if (!empty($filters['start_date'])) {
                $query->whereDate('tanggal_registrasi', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->whereDate('tanggal_registrasi', '<=', $filters['end_date']);
            }

            $customers = $query->latest('tanggal_registrasi')->get();

            // Debug: Check if customers exist
            if ($customers->isEmpty()) {
                return back()->with('error', 'Tidak ada data pelanggan yang sesuai filter');
            }

            \Log::info('Export starting', [
                'total_customers' => $customers->count(),
                'first_customer' => $customers->first()?->reff_id_pelanggan,
                'has_sk' => $customers->first()?->skData ? 'yes' : 'no',
                'has_sr' => $customers->first()?->srData ? 'yes' : 'no',
                'has_gasin' => $customers->first()?->gasInData ? 'yes' : 'no',
            ]);

            // Create Excel using PhpSpreadsheet directly
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = $this->getExportHeaders();
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Style header row
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

            // Set header row height
            $sheet->getRowDimension(1)->setRowHeight(35);

            // Add data
            $row = 2;
            foreach ($customers as $customer) {
                $data = $this->mapCustomerData($customer);
                $col = 'A';
                foreach ($data as $value) {
                    // Convert value to string to handle nulls properly
                    $cellValue = $value ?? '';
                    $sheet->setCellValue($col . $row, $cellValue);
                    $col++;
                }
                $row++;
            }

            // Log for debugging
            \Log::info('Export generated', [
                'total_customers' => $customers->count(),
                'total_rows' => $row - 2,
                'file_size' => filesize($temp_file)
            ]);

            // Add borders to all data cells
            if ($row > 2) {
                $lastDataRow = $row - 1;
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD']
                        ]
                    ]
                ];
                $sheet->getStyle('A1:' . $lastCol . $lastDataRow)->applyFromArray($dataStyle);

                // Zebra striping for better readability
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
            }

            // Auto-size columns with limits
            for ($i = 1; $i <= count($headers); $i++) {
                $columnID = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Freeze panes (freeze first row and first column)
            $sheet->freezePane('B2');

            // Set active cell to A1 (ensures Excel opens at the beginning)
            $sheet->setSelectedCells('A1');

            // Generate filename
            $filename = 'Laporan_Lengkap_' . now()->format('Y-m-d_His') . '.xlsx';

            // Create writer and save to temp
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $temp_file = tempnam(sys_get_temp_dir(), 'excel');
            $writer->save($temp_file);

            // Ensure file exists and is readable
            if (!file_exists($temp_file) || filesize($temp_file) === 0) {
                throw new \Exception('Failed to generate Excel file');
            }

            // Return as download with explicit headers
            return response()->download($temp_file, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    private function getExportHeaders(): array
    {
        return [
            'Reff ID', 'Nama Pelanggan', 'Alamat', 'No Telepon', 'Kelurahan', 'Padukuhan', 'Jenis Pelanggan', 'Tgl Registrasi',
            'SK - Tgl Instalasi', 'SK - Pipa GL Medium (m)', 'SK - Elbow 1/2"', 'SK - SockDraft 1/2"', 'SK - Ball Valve 1/2"',
            'SK - Nipel Selang 1/2"', 'SK - Elbow Reduce 3/4"x1/2"', 'SK - Long Elbow 3/4"', 'SK - Klem Pipa 1/2"',
            'SK - Double Nipple 1/2"', 'SK - Seal Tape', 'SK - Tee 1/2"', 'SK - Total Fitting (pcs)', 'SK - Status', 'SK - Tgl CGP Approval',
            'SR - Tgl Pemasangan', 'SR - Tapping Saddle', 'SR - Coupler 20mm', 'SR - Pipa PE 20mm (m)', 'SR - Elbow 90x20',
            'SR - Transition Fitting', 'SR - Pondasi Tiang (m)', 'SR - Pipa Galvanize 3/4" (m)', 'SR - Klem Pipa',
            'SR - Ball Valve 3/4"', 'SR - Double Nipple 3/4"', 'SR - Long Elbow 3/4"', 'SR - Regulator Service',
            'SR - Coupling MGRT', 'SR - MGRT', 'SR - Casing 1" (m)', 'SR - Sealtape', 'SR - Jenis Tapping',
            'SR - No Seri MGRT', 'SR - Merk MGRT', 'SR - Total Items (pcs)', 'SR - Total Lengths (m)', 'SR - Status', 'SR - Tgl CGP Approval',
            'GasIn - Tgl Gas In', 'GasIn - Status', 'GasIn - Tgl CGP Approval'
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
            $customer->reff_id_pelanggan,
            $customer->nama_pelanggan,
            $customer->alamat,
            $customer->no_telepon,
            $customer->kelurahan,
            $customer->padukuhan,
            $jenisMap[$customer->jenis_pelanggan] ?? $customer->jenis_pelanggan,
            $customer->tanggal_registrasi?->format('Y-m-d'),

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

            $gasIn?->tanggal_gas_in?->format('Y-m-d'),
            $gasIn?->module_status ?? '-',
            $gasIn?->cgp_approved_at?->format('Y-m-d H:i'),
        ];
    }
}