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
    private \App\Services\ComprehensiveExportService $exportService;

    public function __construct(\App\Services\ComprehensiveExportService $exportService)
    {
        $this->exportService = $exportService;
    }

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
                'skData.photoApprovals' => function ($q) {
                    $q->where('photo_status', 'cgp_approved')
                        ->whereNotNull('drive_file_id');
                },
                'srData',
                'srData.photoApprovals' => function ($q) {
                    $q->where('photo_status', 'cgp_approved')
                        ->whereNotNull('drive_file_id');
                },
                'gasInData',
                'gasInData.photoApprovals' => function ($q) {
                    $q->where('photo_status', 'cgp_approved')
                        ->whereNotNull('drive_file_id');
                }
            ])
                ->where('progress_status', 'done')
                ->whereHas('skData', function ($q) {
                    $q->where('module_status', 'completed');
                })
                ->whereHas('srData', function ($q) {
                    $q->where('module_status', 'completed');
                })
                ->whereHas('gasInData', function ($q) {
                    $q->where('module_status', 'completed');
                });

            // Apply filters
            if ($filters['search']) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
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

            $spreadsheet = $this->exportService->generateSpreadsheet($customers);
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
            'skData.photoApprovals' => function ($q) {
                $q->where('photo_status', 'cgp_approved')
                    ->whereNotNull('drive_file_id');
            },
            'srData',
            'srData.photoApprovals' => function ($q) {
                $q->where('photo_status', 'cgp_approved')
                    ->whereNotNull('drive_file_id');
            },
            'gasInData',
            'gasInData.photoApprovals' => function ($q) {
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
            $query->where(function ($q) use ($search) {
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
}
