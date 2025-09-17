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

class ReportDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = [
                'padukuhan' => $request->input('padukuhan'),
                'kelurahan' => $request->input('kelurahan'),
                'jenis_pelanggan' => $request->input('jenis_pelanggan'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // Get unique padukuhan for filter dropdown
            $padukuhanList = CalonPelanggan::whereNotNull('padukuhan')
                ->where('padukuhan', '!=', '')
                ->distinct()
                ->pluck('padukuhan')
                ->sort()
                ->values();

            // Get unique kelurahan for filter dropdown
            $kelurahanList = CalonPelanggan::whereNotNull('kelurahan')
                ->where('kelurahan', '!=', '')
                ->distinct()
                ->pluck('kelurahan')
                ->sort()
                ->values();

        // Build base query
        $query = CalonPelanggan::query();

        // Apply filters
        if ($filters['padukuhan']) {
            $query->where('padukuhan', 'like', '%' . $filters['padukuhan'] . '%');
        }
        if ($filters['kelurahan']) {
            $query->where('kelurahan', 'like', '%' . $filters['kelurahan'] . '%');
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

        // Get customer IDs for detailed module stats
        $customerIds = $query->pluck('reff_id_pelanggan');

        // Overall statistics
        $totalCustomers = $query->count();

        // Statistics by jenis pelanggan
        $statsByJenis = $query->groupBy('jenis_pelanggan')
            ->select('jenis_pelanggan', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('jenis_pelanggan');

        // Progress statistics
        $progressStats = $query->groupBy('progress_status')
            ->select('progress_status', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('progress_status');

        // Module completion statistics
        $moduleStats = $this->getModuleStatistics($customerIds);

        // Photo evidence statistics
        $photoStats = $this->getPhotoStatistics($customerIds);

        // Detailed breakdown by padukuhan and jenis pelanggan
        $breakdown = $this->getDetailedBreakdown($filters);

        // For AJAX requests
        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_customers' => $totalCustomers,
                    'stats_by_jenis' => $statsByJenis,
                    'progress_stats' => $progressStats,
                    'module_stats' => $moduleStats,
                    'photo_stats' => $photoStats,
                    'breakdown' => $breakdown,
                    'filters' => $filters,
                ]
            ]);
        }

            // For debugging, use simple view first
            if ($request->has('simple')) {
                return view('reports.simple', compact(
                    'totalCustomers',
                    'statsByJenis',
                    'moduleStats',
                    'padukuhanList'
                ));
            }

            return view('reports.dashboard', compact(
                'totalCustomers',
                'statsByJenis',
                'progressStats',
                'moduleStats',
                'photoStats',
                'breakdown',
                'padukuhanList',
                'kelurahanList',
                'filters'
            ));
        } catch (\Exception $e) {
            // Debug error
            if (config('app.debug')) {
                throw $e;
            }

            // Fallback data
            return view('reports.dashboard', [
                'totalCustomers' => 0,
                'statsByJenis' => collect(),
                'progressStats' => collect(),
                'moduleStats' => ['sk' => collect(), 'sr' => collect(), 'gas_in' => collect()],
                'photoStats' => ['counts_by_module' => collect(), 'status_distribution' => collect(), 'by_module_status' => collect()],
                'breakdown' => collect(),
                'padukuhanList' => collect(),
                'kelurahanList' => collect(),
                'filters' => $filters ?? []
            ]);
        }
    }

    private function getModuleStatistics($customerIds)
    {
        // Count evidence based on actual data records in each module
        $skEvidenceCount = SkData::whereIn('reff_id_pelanggan', $customerIds)->count();

        $srEvidenceCount = SrData::whereIn('reff_id_pelanggan', $customerIds)->count();

        $gasInEvidenceCount = GasInData::whereIn('reff_id_pelanggan', $customerIds)->count();

        // Also get module status breakdown for additional info
        $skStats = SkData::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('status');

        $srStats = SrData::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('status');

        $gasInStats = GasInData::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('status');

        // Add evidence count to each module stats
        $skStats->put('evidence_uploaded', (object)['total' => $skEvidenceCount]);
        $srStats->put('evidence_uploaded', (object)['total' => $srEvidenceCount]);
        $gasInStats->put('evidence_uploaded', (object)['total' => $gasInEvidenceCount]);

        return [
            'sk' => $skStats,
            'sr' => $srStats,
            'gas_in' => $gasInStats,
        ];
    }

    private function getPhotoStatistics($customerIds)
    {
        // Photo counts by module
        $photoCountsByModule = PhotoApproval::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('module_name')
            ->select('module_name', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('module_name');

        // Photo status distribution
        $photoStatusStats = PhotoApproval::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('photo_status')
            ->select('photo_status', DB::raw('count(*) as total'))
            ->get()
            ->keyBy('photo_status');

        // Photos by module and status
        $photosByModuleStatus = PhotoApproval::whereIn('reff_id_pelanggan', $customerIds)
            ->groupBy('module_name', 'photo_status')
            ->select('module_name', 'photo_status', DB::raw('count(*) as total'))
            ->get()
            ->groupBy('module_name');

        return [
            'counts_by_module' => $photoCountsByModule,
            'status_distribution' => $photoStatusStats,
            'by_module_status' => $photosByModuleStatus,
        ];
    }

    private function getDetailedBreakdown($filters)
    {
        $query = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
            ->select([
                'reff_id_pelanggan',
                'nama_pelanggan',
                'padukuhan',
                'kelurahan',
                'jenis_pelanggan',
                'progress_status',
                'tanggal_registrasi'
            ]);

        // Apply same filters
        if ($filters['padukuhan']) {
            $query->where('padukuhan', 'like', '%' . $filters['padukuhan'] . '%');
        }
        if ($filters['kelurahan']) {
            $query->where('kelurahan', 'like', '%' . $filters['kelurahan'] . '%');
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

        $customers = $query->get();

        // Group by padukuhan and jenis pelanggan
        $breakdown = $customers->groupBy('padukuhan')->map(function ($customersByPadukuhan) {
            return $customersByPadukuhan->groupBy('jenis_pelanggan')->map(function ($customersByJenis) {
                $total = $customersByJenis->count();

                // Count evidence uploads by module (based on actual data records)
                $customerIds = $customersByJenis->pluck('reff_id_pelanggan');

                $evidenceCount = [
                    'sk' => SkData::whereIn('reff_id_pelanggan', $customerIds)->count(),
                    'sr' => SrData::whereIn('reff_id_pelanggan', $customerIds)->count(),
                    'gas_in' => GasInData::whereIn('reff_id_pelanggan', $customerIds)->count(),
                ];

                return [
                    'total' => $total,
                    'evidence_counts' => $evidenceCount,
                    'customers' => $customersByJenis->map(function ($customer) {
                        return [
                            'reff_id' => $customer->reff_id_pelanggan,
                            'nama' => $customer->nama_pelanggan,
                            'progress' => $customer->progress_status,
                            'tanggal_registrasi' => $customer->tanggal_registrasi?->format('Y-m-d'),
                            'has_sk_evidence' => SkData::where('reff_id_pelanggan', $customer->reff_id_pelanggan)->exists(),
                            'has_sr_evidence' => SrData::where('reff_id_pelanggan', $customer->reff_id_pelanggan)->exists(),
                            'has_gas_in_evidence' => GasInData::where('reff_id_pelanggan', $customer->reff_id_pelanggan)->exists(),
                        ];
                    })->values()
                ];
            });
        });

        return $breakdown;
    }

    public function export(Request $request)
    {
        // Implementation for exporting report data
        // This could export to Excel, PDF, etc.
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}