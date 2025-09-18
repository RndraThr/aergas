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
        // TODO: Implement export functionality
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}