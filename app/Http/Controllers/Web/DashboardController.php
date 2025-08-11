<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\CalonPelanggan;
use App\Models\AuditLog;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DashboardController extends Controller implements HasMiddleware
{
    public function __construct(private ReportService $reportService) {}

    // Laravel 11+ controller middleware
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            // sesuaikan daftar role dengan punyamu
            new Middleware('role:super_admin,admin,sk,sr,gas_in,validasi,tracer,mgrt,pic'),
        ];
    }

    /** Blade dashboard */
    public function index(Request $request)
    {
        return view('dashboard'); // pastikan resources/views/aergas/dashboard.blade.php ada
    }

    /** GET /api/aergas/quick-stats */
    public function quickStats(Request $request): JsonResponse
    {
        try {
            [$from, $to, $area] = $this->resolveFilters($request);

            $cacheKey = sprintf(
                'dashboard.quickstats:%s:%s:%s',
                optional($from)->toDateString(),
                optional($to)->toDateString(),
                $area ?? 'all'
            );

            $data = Cache::remember($cacheKey, 60, function () use ($from, $to, $area) {
                $base = CalonPelanggan::query();
                $this->applyDateAreaFilters($base, $from, $to, $area);

                $total = (clone $base)->count();
                $done  = (clone $base)->where('progress_status', 'done')->count();
                $batal = (clone $base)->where('progress_status', 'batal')->count();
                $inProgress = max(0, $total - $done - $batal);

                $pendingValidasi = (clone $base)
                    ->where('progress_status', 'validasi')
                    ->whereIn('status', ['pending','menunda'])
                    ->count();

                $labelsWorkflow = ['validasi','sk','sr','mgrt','gas_in','jalur_pipa','penyambungan','done','batal'];
                $perModul = [];
                foreach ($labelsWorkflow as $ps) {
                    $perModul[$ps] = (clone $base)->where('progress_status', $ps)->count();
                }

                $completionRateThisMonth = null;
                $avgCompletionDays = null;
                try { $completionRateThisMonth = $this->reportService->getMonthlyCompletionRate(); } catch (Exception) {}
                try { $avgCompletionDays = $this->reportService->getAverageCompletionTimeInDays(); } catch (Exception) {}

                return [
                    'totals' => [
                        'total_customers'  => $total,
                        'in_progress'      => $inProgress,
                        'done'             => $done,
                        'batal'            => $batal,
                        'pending_validasi' => $pendingValidasi,
                    ],
                    'modules' => $perModul,
                    'rates' => [
                        'completion_rate_this_month' => $completionRateThisMonth,
                        'avg_completion_days'        => $avgCompletionDays,
                    ],
                    'updated_at' => now(),
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Log::error('quickStats error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    /** GET /api/aergas/chart-data */
    public function chartData(Request $request): JsonResponse
    {
        try {
            [$from, $to, $area] = $this->resolveFilters($request);

            $cacheKey = sprintf(
                'dashboard.chartdata:%s:%s:%s',
                optional($from)->toDateString(),
                optional($to)->toDateString(),
                $area ?? 'all'
            );

            $payload = Cache::remember($cacheKey, 60, function () use ($from, $to, $area) {
                $labelsWorkflow = ['validasi','sk','sr','mgrt','gas_in','jalur_pipa','penyambungan','done','batal'];
                $workflowCounts = $this->countByProgressStatus($from, $to, $area, $labelsWorkflow);

                $monthly = $this->monthlyDoneTrend($from, $to, $area, 12);

                $labelsStatus = ['pending','lanjut','menunda','batal'];
                $statusCounts = $this->countByStatus($from, $to, $area, $labelsStatus);

                return [
                    'workflow' => [
                        'labels' => $labelsWorkflow,
                        'datasets' => [[ 'label' => 'Progress', 'data' => array_values($workflowCounts) ]]
                    ],
                    'monthly' => [
                        'labels'   => array_column($monthly, 'label'),
                        'datasets' => [[ 'label' => 'Selesai per bulan', 'data' => array_column($monthly, 'value') ]]
                    ],
                    'status' => [
                        'labels' => $labelsStatus,
                        'datasets' => [[ 'label' => 'Status Pelanggan', 'data' => array_values($statusCounts) ]]
                    ],
                    'updated_at' => now(),
                ];
            });

            return response()->json(['success' => true, 'data' => $payload]);
        } catch (Exception $e) {
            Log::error('chartData error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    /** GET /api/aergas/activity-timeline */
    public function activityTimeline(Request $request): JsonResponse
    {
        try {
            $limit = (int) min(max((int) $request->get('limit', 15), 1), 100);

            $logs = AuditLog::with('user:id,name,username')
                ->latest('created_at')
                ->limit($limit)
                ->get(['id','event','model_type','model_id','description','severity','meta','user_id','created_at']);

            $timeline = $logs->map(fn($log) => [
                'id'          => $log->id,
                'event'       => $log->event,
                'severity'    => $log->severity,
                'model'       => class_basename($log->model_type),
                'model_id'    => $log->model_id,
                'actor'       => $log->user?->name ?? $log->user?->username ?? 'System',
                'description' => $log->description,
                'meta'        => $log->meta,
                'created_at'  => $log->created_at,
            ]);

            return response()->json(['success' => true, 'data' => $timeline]);
        } catch (Exception $e) {
            Log::error('activityTimeline error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    /* ============================ Helpers ============================ */

    private function resolveFilters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : null;
        $to   = $request->filled('to')   ? Carbon::parse($request->get('to'))->endOfDay()     : null;
        $area = $request->filled('wilayah_area') ? $request->string('wilayah_area')->toString() : null;
        return [$from, $to, $area];
    }

    private function applyDateAreaFilters($query, ?Carbon $from, ?Carbon $to, ?string $area): void
    {
        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);
        if ($area) $query->where('wilayah_area', $area);
    }

    private function countByProgressStatus(?Carbon $from, ?Carbon $to, ?string $area, array $labels): array
    {
        $q = CalonPelanggan::query();
        $this->applyDateAreaFilters($q, $from, $to, $area);

        $rows = $q->select('progress_status', DB::raw('COUNT(*) as c'))
            ->groupBy('progress_status')->pluck('c','progress_status');

        $out = [];
        foreach ($labels as $ps) {
            $out[$ps] = (int) ($rows[$ps] ?? 0);
        }
        return $out;
    }

    private function countByStatus(?Carbon $from, ?Carbon $to, ?string $area, array $labels): array
    {
        $q = CalonPelanggan::query();
        $this->applyDateAreaFilters($q, $from, $to, $area);

        $rows = $q->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')->pluck('c','status');

        $out = [];
        foreach ($labels as $s) {
            $out[$s] = (int) ($rows[$s] ?? 0);
        }
        return $out;
    }

    /** Tren 12 bulan terakhir untuk progress_status = 'done' */
    private function monthlyDoneTrend(?Carbon $from, ?Carbon $to, ?string $area, int $months = 12): array
    {
        $end = $to ? $to->copy() : now();
        $start = $from ? $from->copy() : now()->copy()->subMonths($months - 1)->startOfMonth();

        $q = CalonPelanggan::query()
            ->where('progress_status', 'done')
            ->whereBetween('updated_at', [$start->copy()->startOfMonth(), $end->copy()->endOfMonth()]);

        if ($area) $q->where('wilayah_area', $area);

        $rows = $q->select(
                DB::raw("DATE_FORMAT(updated_at, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as c')
            )
            ->groupBy('ym')
            ->pluck('c','ym');

        $out = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();
        while ($cursor <= $last) {
            $ym = $cursor->format('Y-m');
            $out[] = [
                'label' => $cursor->locale('id_ID')->isoFormat('MMM YYYY'),
                'value' => (int) ($rows[$ym] ?? 0),
            ];
            $cursor->addMonth();
        }

        return $out;
    }
}
