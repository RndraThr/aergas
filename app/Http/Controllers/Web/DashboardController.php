<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\CalonPelanggan;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use App\Models\PhotoApproval;
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

   public static function middleware(): array
   {
       return [
           new Middleware('auth'),
        //    new Middleware('role:super_admin,admin,sk,sr,gas_in,validasi,tracer,pic'),
       ];
   }

   public function index(Request $request)
   {
       return view('dashboard');
   }



    private function getTotalStats(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $base = CalonPelanggan::query();
        $this->applyFilters($base, $from, $to, $kelurahan, $padukuhan);

        $total = (clone $base)->count();
        $totalActive = (clone $base)->where('status', '!=', 'batal')->count(); // Exclude batal customers
        $done = (clone $base)->where('progress_status', 'done')->where('status', '!=', 'batal')->count();
        $batal = (clone $base)->where('status', 'batal')->count(); // Use status batal, not progress_status
        $inProgress = $totalActive - $done;

        return [
            'total_customers' => $total,
            'in_progress' => max(0, $inProgress),
            'done' => $done,
            'batal' => $batal,
            'pending_validasi' => (clone $base)->where('status', 'pending')->count(),
            'completion_rate' => $totalActive > 0 ? round(($done / $totalActive) * 100, 1) : 0, // Base on active customers only
        ];
    }
    public function getData(Request $request): JsonResponse
    {
        try {
            [$from, $to, $kelurahan, $padukuhan] = $this->resolveFilters($request);

            $data = [
                'totals' => $this->getTotalStats($from, $to, $kelurahan, $padukuhan),
                'modules' => $this->getModuleStats($from, $to, $kelurahan, $padukuhan),
                'charts' => $this->getChartData($from, $to, $kelurahan, $padukuhan),
                'donut_stats' => $this->getDonutChartData(),
                'activities' => $this->getRecentActivities(10),
                'photos' => $this->getPhotoStats(),
                'performance' => $this->getPerformanceMetrics(),
                'updated_at' => now(),
            ];

            // Add customers data for maps if requested
            if ($request->has('include_coordinates')) {
                $data['customers'] = $this->getCustomersForMaps($from, $to, $kelurahan, $padukuhan);
                $data['maps_stats'] = $this->getMapsStats($from, $to, $kelurahan, $padukuhan);
                $data['filter_options'] = $this->getFilterOptions();
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Log::error('Dashboard data error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    private function getModuleStats(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $base = CalonPelanggan::query();
        $this->applyFilters($base, $from, $to, $kelurahan, $padukuhan);

        $workflowLabels = ['validasi', 'sk', 'sr', 'gas_in', 'done', 'batal'];
        $progressCounts = [];

        foreach ($workflowLabels as $status) {
            $progressCounts[$status] = (clone $base)->where('progress_status', $status)->count();
        }

        $moduleData = [
            'sk' => [
                'total' => SkData::count(),
                'draft' => SkData::where('module_status', 'draft')->count(),
                'ready' => SkData::where('module_status', 'tracer_review')->count(),
                'completed' => SkData::where('module_status', 'completed')->count(),
                'in_progress' => SkData::whereIn('module_status', ['ai_validation', 'tracer_review', 'cgp_review'])->count(),
                'rejected' => SkData::where('module_status', 'rejected')->count(),
            ],
            'sr' => [
                'total' => SrData::count(),
                'draft' => SrData::where('module_status', 'draft')->count(),
                'ready' => SrData::where('module_status', 'tracer_review')->count(),
                'completed' => SrData::where('module_status', 'completed')->count(),
                'in_progress' => SrData::whereIn('module_status', ['ai_validation', 'tracer_review', 'cgp_review'])->count(),
                'rejected' => SrData::where('module_status', 'rejected')->count(),
            ],
            'gas_in' => [
                'total' => GasInData::count(),
                'draft' => GasInData::where('module_status', 'draft')->count(),
                'ready' => GasInData::where('module_status', 'tracer_review')->count(),
                'completed' => GasInData::where('module_status', 'completed')->count(),
                'in_progress' => GasInData::whereIn('module_status', ['ai_validation', 'tracer_review', 'cgp_review'])->count(),
                'rejected' => GasInData::where('module_status', 'rejected')->count(),
            ],
        ];

        // Calculate pie chart data for modules
        $pieChartData = [
            'total_by_module' => [
                'labels' => ['SK Module', 'SR Module', 'Gas In Module'],
                'data' => [
                    $moduleData['sk']['total'],
                    $moduleData['sr']['total'],
                    $moduleData['gas_in']['total']
                ],
                'colors' => ['#EF4444', '#10B981', '#3B82F6']
            ],
            'completion_status' => [
                'labels' => ['Completed', 'In Progress', 'Draft', 'Rejected'],
                'data' => [
                    $moduleData['sk']['completed'] + $moduleData['sr']['completed'] + $moduleData['gas_in']['completed'],
                    $moduleData['sk']['in_progress'] + $moduleData['sr']['in_progress'] + $moduleData['gas_in']['in_progress'],
                    $moduleData['sk']['draft'] + $moduleData['sr']['draft'] + $moduleData['gas_in']['draft'],
                    $moduleData['sk']['rejected'] + $moduleData['sr']['rejected'] + $moduleData['gas_in']['rejected']
                ],
                'colors' => ['#10B981', '#F59E0B', '#6B7280', '#EF4444']
            ],
            'progress_distribution' => [
                'labels' => ['Validasi', 'SK', 'SR', 'Gas In', 'Done', 'Batal'],
                'data' => [
                    $progressCounts['validasi'] ?? 0,
                    $progressCounts['sk'] ?? 0,
                    $progressCounts['sr'] ?? 0,
                    $progressCounts['gas_in'] ?? 0,
                    $progressCounts['done'] ?? 0,
                    $progressCounts['batal'] ?? 0
                ],
                'colors' => ['#8B5CF6', '#EF4444', '#10B981', '#3B82F6', '#059669', '#DC2626']
            ]
        ];

        return [
            'progress_distribution' => $progressCounts,
            'module_details' => $moduleData,
            'pie_charts' => $pieChartData,
            // Also include direct module access for frontend compatibility
            'sk' => $moduleData['sk'],
            'sr' => $moduleData['sr'],
            'gas_in' => $moduleData['gas_in'],
        ];
    }

    private function getChartData(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $monthlyTrend = $this->getMonthlyTrend($from, $to, $kelurahan, $padukuhan);
        $statusDistribution = $this->getStatusDistribution($from, $to, $kelurahan, $padukuhan);
        $progressFlow = $this->getProgressFlow($from, $to, $kelurahan, $padukuhan);

        return [
            'monthly_completion' => [
                'labels' => array_column($monthlyTrend, 'label'),
                'data' => array_column($monthlyTrend, 'value'),
            ],
            'status_distribution' => $statusDistribution,
            'progress_flow' => $progressFlow,
        ];
    }

    private function getMonthlyTrend(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan, int $months = 12): array
    {
        $end = $to ?: now();
        $start = $from ?: now()->subMonths($months - 1)->startOfMonth();

        $q = CalonPelanggan::where('progress_status', 'done')
            ->whereBetween('updated_at', [$start->startOfMonth(), $end->endOfMonth()]);

        if ($kelurahan) $q->where('kelurahan', $kelurahan);
        if ($padukuhan) $q->where('padukuhan', $padukuhan);

        $rows = $q->select(
            DB::raw("DATE_FORMAT(updated_at, '%Y-%m') as month"),
            DB::raw('COUNT(*) as count')
        )->groupBy('month')->pluck('count', 'month');

        $result = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor <= $end->startOfMonth()) {
            $monthKey = $cursor->format('Y-m');
            $result[] = [
                'label' => $cursor->format('M Y'),
                'value' => (int) ($rows[$monthKey] ?? 0),
            ];
            $cursor->addMonth();
        }

        return $result;
    }


    /**
     * Get data for donut/pie charts
     */
    private function getDonutChartData(): array
    {
        // Photo approval status distribution
        $photoStats = [
            'approved' => PhotoApproval::where('photo_status', 'cgp_approved')->count(),
            'pending_cgp' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'pending_tracer' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
            'ai_validation' => PhotoApproval::where('photo_status', 'ai_pending')->count(),
            'rejected' => PhotoApproval::whereIn('photo_status', ['ai_rejected', 'tracer_rejected', 'cgp_rejected'])->count(),
        ];

        // Customer jenis distribution
        $jenisStats = CalonPelanggan::select('jenis_pelanggan')
            ->selectRaw('count(*) as total')
            ->whereNotNull('jenis_pelanggan')
            ->groupBy('jenis_pelanggan')
            ->get()
            ->mapWithKeys(function($item) {
                return [ucfirst(str_replace('_', ' ', $item->jenis_pelanggan)) => $item->total];
            })
            ->toArray();

        return [
            'photo_approval' => [
                'labels' => ['Approved', 'Pending CGP', 'Pending Tracer', 'AI Validation', 'Rejected'],
                'data' => [
                    $photoStats['approved'],
                    $photoStats['pending_cgp'],
                    $photoStats['pending_tracer'],
                    $photoStats['ai_validation'],
                    $photoStats['rejected']
                ],
                'colors' => ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444']
            ],
            'customer_types' => [
                'labels' => array_keys($jenisStats),
                'data' => array_values($jenisStats),
                'colors' => ['#6366F1', '#EC4899', '#14B8A6', '#F97316', '#84CC16']
            ]
        ];
    }


   private function getStatusDistribution(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $base = CalonPelanggan::query();
        $this->applyFilters($base, $from, $to, $kelurahan, $padukuhan);

        $statuses = ['pending', 'lanjut', 'menunda', 'batal'];
        $distribution = [];

        foreach ($statuses as $status) {
            $count = (clone $base)->where('status', $status)->count();
            $distribution[] = [
                'label' => ucfirst($status),
                'value' => $count,
            ];
        }

        return $distribution;
    }

   private function getProgressFlow(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
{
    $base = CalonPelanggan::query();
    $this->applyFilters($base, $from, $to, $kelurahan, $padukuhan);

    $progressSteps = ['validasi', 'sk', 'sr', 'gas_in', 'done'];
    $flow = [];

    foreach ($progressSteps as $step) {
        $count = (clone $base)->where('progress_status', $step)->count();
        $flow[] = [
            'step' => $step,
            'count' => $count,
            'label' => ucwords(str_replace('_', ' ', $step)),
        ];
    }

    return $flow;
}

   private function getRecentActivities(int $limit = 10): array
    {
        $logs = AuditLog::with('user:id,name,username')
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', 'action', 'model_type', 'model_id', 'description', 'user_id', 'created_at']);

        return $logs->map(fn($log) => [
            'id' => $log->id,
            'event' => $log->action, // Gunakan 'action' bukan 'event'
            'model' => class_basename($log->model_type),
            'model_id' => $log->model_id,
            'actor' => $log->user?->name ?? 'System',
            'description' => $log->description,
            'time_ago' => $log->created_at->diffForHumans(),
            'created_at' => $log->created_at,
        ])->toArray();
    }

   private function getPhotoStats(): array
   {
       $total = PhotoApproval::count();
       $pendingTracer = PhotoApproval::where('photo_status', 'tracer_pending')->count();
       $pendingCgp = PhotoApproval::where('photo_status', 'cgp_pending')->count();
       $approved = PhotoApproval::where('photo_status', 'cgp_approved')->count();

       return [
           'total_photos' => $total,
           'pending_tracer' => $pendingTracer,
           'pending_cgp' => $pendingCgp,
           'approved' => $approved,
           'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
       ];
   }

   private function getPerformanceMetrics(): array
   {
       try {
           $completionRate = $this->reportService->getMonthlyCompletionRate();
           $avgDays = $this->reportService->getAverageCompletionTimeInDays();
       } catch (Exception) {
           $completionRate = null;
           $avgDays = null;
       }

       $slaCompliance = $this->calculateSlaCompliance();

       return [
           'monthly_completion_rate' => $completionRate,
           'avg_completion_days' => $avgDays,
           'sla_compliance' => $slaCompliance,
       ];
   }

   private function calculateSlaCompliance(): array
   {
       $tracerViolations = PhotoApproval::where('photo_status', 'tracer_pending')
           ->where('created_at', '<', now()->subHours(24))
           ->count();

       $cgpViolations = PhotoApproval::where('photo_status', 'cgp_pending')
           ->where('created_at', '<', now()->subHours(48))
           ->count();

       $totalPending = PhotoApproval::whereIn('photo_status', ['tracer_pending', 'cgp_pending'])->count();
       $violations = $tracerViolations + $cgpViolations;
       $compliance = $totalPending > 0 ? round((($totalPending - $violations) / $totalPending) * 100, 1) : 100;

       return [
           'tracer_violations' => $tracerViolations,
           'cgp_violations' => $cgpViolations,
           'total_violations' => $violations,
           'compliance_percentage' => $compliance,
       ];
   }

   private function resolveFilters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->get('to'))->endOfDay() : null;
        $kelurahan = $request->filled('kelurahan') ? $request->string('kelurahan')->toString() : null;
        $padukuhan = $request->filled('padukuhan') ? $request->string('padukuhan')->toString() : null;

        return [$from, $to, $kelurahan, $padukuhan];
    }

    private function applyFilters($query, ?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): void
    {
        if ($from) $query->where('created_at', '>=', $from);
        if ($to) $query->where('created_at', '<=', $to);
        if ($kelurahan) $query->where('kelurahan', $kelurahan);
        if ($padukuhan) $query->where('padukuhan', $padukuhan);
    }

   public function getInstallationTrend(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'daily');
            $days = (int) $request->get('days', 30);
            $module = $request->get('module', 'all');

            $endDate = now();
            $startDate = now()->subDays($days);

            $datasets = [];

            if ($module === 'all' || $module === 'sk') {
                $skData = $this->getModuleInstallationData('sk', $startDate, $endDate, $period);
                $datasets[] = [
                    'label' => 'SK Installations',
                    'data' => $skData,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 1,
                ];
            }

            if ($module === 'all' || $module === 'sr') {
                $srData = $this->getModuleInstallationData('sr', $startDate, $endDate, $period);
                $datasets[] = [
                    'label' => 'SR Installations',
                    'data' => $srData,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ];
            }

            if ($module === 'all' || $module === 'gas_in') {
                $gasInData = $this->getModuleInstallationData('gas_in', $startDate, $endDate, $period);
                $datasets[] = [
                    'label' => 'Gas In Completions',
                    'data' => $gasInData,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)',
                    'borderColor' => '#F59E0B',
                    'borderWidth' => 1,
                ];
            }

            if ($module === 'all') {
                $totalData = [];
                $labels = $this->generateDateLabels($startDate, $endDate, $period);

                for ($i = 0; $i < count($labels); $i++) {
                    $total = 0;
                    foreach ($datasets as $dataset) {
                        $total += $dataset['data'][$i] ?? 0;
                    }
                    $totalData[] = $total;
                }

                $datasets[] = [
                    'label' => 'Total Trend',
                    'data' => $totalData,
                    'type' => 'line',
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#EF4444',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                ];
            }

            $labels = $this->generateDateLabels($startDate, $endDate, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => $datasets,
                    'period' => $period,
                    'total_days' => $days,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Installation trend error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getModuleInstallationData(string $module, Carbon $startDate, Carbon $endDate, string $period): array
    {
        $dateField = match ($module) {
            'sk' => 'tanggal_instalasi',
            'sr' => 'tanggal_pemasangan',
            'gas_in' => 'tanggal_gas_in',
            default => 'created_at',
        };

        $modelClass = match ($module) {
            'sk' => SkData::class,
            'sr' => SrData::class,
            'gas_in' => GasInData::class,
            default => SkData::class,
        };

        $query = $modelClass::whereBetween($dateField, [$startDate, $endDate])
            ->whereNotNull($dateField);

        $groupBy = match ($period) {
            'daily' => "DATE({$dateField})",
            'weekly' => "YEARWEEK({$dateField}, 1)",
            'monthly' => "DATE_FORMAT({$dateField}, '%Y-%m')",
            default => "DATE({$dateField})",
        };

        $results = $query->select(
            DB::raw("{$groupBy} as period"),
            DB::raw('COUNT(*) as count')
        )->groupBy('period')->pluck('count', 'period');

        return $this->fillMissingPeriods($results->toArray(), $startDate, $endDate, $period);
    }


private function fillMissingPeriods(array $data, Carbon $startDate, Carbon $endDate, string $period): array
{
    $filled = [];
    $current = $startDate->copy();
    $maxIterations = 400;
    $iterations = 0;

    while ($current <= $endDate && $iterations < $maxIterations) {
        $key = match ($period) {
            'daily' => $current->format('Y-m-d'),
            'weekly' => $current->format('oW'),
            'monthly' => $current->format('Y-m'),
            default => $current->format('Y-m-d'),
        };

        $filled[] = (int) ($data[$key] ?? 0);

        $current = match ($period) {
            'daily' => $current->addDay(),
            'weekly' => $current->addWeek(),
            'monthly' => $current->addMonth(),
            default => $current->addDay(),
        };

        $iterations++;
    }

    return $filled;
}

private function generateDateLabels(Carbon $startDate, Carbon $endDate, string $period): array
{
    $labels = [];
    $current = $startDate->copy();
    $maxIterations = 400;
    $iterations = 0;

    while ($current <= $endDate && $iterations < $maxIterations) {
        $labels[] = match ($period) {
            'daily' => $current->format('M j'),
            'weekly' => 'Week ' . $current->weekOfYear,
            'monthly' => $current->format('M Y'),
            default => $current->format('M j'),
        };

        $current = match ($period) {
            'daily' => $current->addDay(),
            'weekly' => $current->addWeek(),
            'monthly' => $current->addMonth(),
            default => $current->addDay(),
        };

        $iterations++;
    }

    return $labels;
}
    public function getActivityMetrics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'daily');
            $days = (int) $request->get('days', 7);

            $cacheKey = "activity.metrics:{$period}:{$days}";

            $data = Cache::remember($cacheKey, 600, function () use ($period, $days) {
                $endDate = now();
                $startDate = now()->subDays($days);

                return [
                    'photo_uploads' => $this->getPhotoUploadTrend($startDate, $endDate, $period),
                    'approvals' => $this->getApprovalTrend($startDate, $endDate, $period),
                    'customer_registrations' => $this->getCustomerRegistrationTrend($startDate, $endDate, $period),
                    'completions' => $this->getCompletionTrend($startDate, $endDate, $period),
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Log::error('Activity metrics error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    private function getPhotoUploadTrend(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $groupBy = match ($period) {
            'daily' => 'DATE(created_at)',
            'weekly' => 'YEARWEEK(created_at, 1)',
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => 'DATE(created_at)',
        };

        $results = PhotoApproval::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw("{$groupBy} as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period');

        return $this->fillMissingPeriods($results->toArray(), $startDate, $endDate, $period);
    }

    private function getApprovalTrend(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $groupBy = match ($period) {
            'daily' => 'DATE(cgp_approved_at)',
            'weekly' => 'YEARWEEK(cgp_approved_at, 1)',
            'monthly' => "DATE_FORMAT(cgp_approved_at, '%Y-%m')",
            default => 'DATE(cgp_approved_at)',
        };

        $results = PhotoApproval::whereBetween('cgp_approved_at', [$startDate, $endDate])
            ->where('photo_status', 'cgp_approved')
            ->select(DB::raw("{$groupBy} as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period');

        return $this->fillMissingPeriods($results->toArray(), $startDate, $endDate, $period);
    }

    private function getCustomerRegistrationTrend(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $groupBy = match ($period) {
            'daily' => 'DATE(tanggal_registrasi)',
            'weekly' => 'YEARWEEK(tanggal_registrasi, 1)',
            'monthly' => "DATE_FORMAT(tanggal_registrasi, '%Y-%m')",
            default => 'DATE(tanggal_registrasi)',
        };

        $results = CalonPelanggan::whereBetween('tanggal_registrasi', [$startDate, $endDate])
            ->select(DB::raw("{$groupBy} as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period');

        return $this->fillMissingPeriods($results->toArray(), $startDate, $endDate, $period);
    }

    private function getCompletionTrend(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $groupBy = match ($period) {
            'daily' => 'DATE(updated_at)',
            'weekly' => 'YEARWEEK(updated_at, 1)',
            'monthly' => "DATE_FORMAT(updated_at, '%Y-%m')",
            default => 'DATE(updated_at)',
        };

        $results = CalonPelanggan::whereBetween('updated_at', [$startDate, $endDate])
            ->where('progress_status', 'done')
            ->select(DB::raw("{$groupBy} as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period');

        return $this->fillMissingPeriods($results->toArray(), $startDate, $endDate, $period);
    }

    /**
     * Get customers data with coordinates for maps display
     */
    private function getCustomersForMaps(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $query = CalonPelanggan::withCoordinates(); // Use our new scope

        // Apply filters
        $this->applyFilters($query, $from, $to, $kelurahan, $padukuhan);

        return $query->select([
                'reff_id_pelanggan',
                'nama_pelanggan',
                'alamat',
                'kelurahan',
                'padukuhan',
                'latitude',
                'longitude',
                'status',
                'progress_status',
                'jenis_pelanggan',
                'tanggal_registrasi'
            ])
            ->limit(500) // Limit to prevent performance issues
            ->get()
            ->map(function ($customer) {
                return $customer->getMarkerInfo();
            })
            ->toArray();
    }

    /**
     * Get statistics specifically for maps component
     */
    private function getMapsStats(?Carbon $from, ?Carbon $to, ?string $kelurahan, ?string $padukuhan): array
    {
        $base = CalonPelanggan::query();
        $this->applyFilters($base, $from, $to, $kelurahan, $padukuhan);

        // Get counts based on coordinates (for coordinate-specific stats only)
        $totalCustomers = (clone $base)->count();
        $customersWithCoordinates = (clone $base)->withCoordinates()->count();
        $customersWithoutCoordinates = $totalCustomers - $customersWithCoordinates;

        // Progress status counts for ACTIVE customers only (exclude status batal)
        $activeBase = (clone $base)->where('status', '!=', 'batal');
        $totalActiveCustomers = $activeBase->count();
        $done = (clone $activeBase)->where('progress_status', 'done')->count();
        $batalProgress = (clone $base)->where('progress_status', 'batal')->count(); // Keep for reference

        // Status counts for reference (if needed)
        $allStatusCounts = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'lanjut' => (clone $base)->where('status', 'lanjut')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'batal' => (clone $base)->where('status', 'batal')->count(),
        ];

        // Progress status counts for pending review calculation (active customers only)
        $progressCounts = [
            'validasi' => (clone $activeBase)->where('progress_status', 'validasi')->count(),
            'sk' => (clone $activeBase)->where('progress_status', 'sk')->count(),
            'sr' => (clone $activeBase)->where('progress_status', 'sr')->count(),
            'gas_in' => (clone $activeBase)->where('progress_status', 'gas_in')->count(),
        ];

        // Calculate pending review (active customers that are not done)
        $pendingReview = $totalActiveCustomers - $done;

        // Photo approval stats (global, not filtered)
        $photoApproved = PhotoApproval::where('photo_status', 'cgp_approved')->count();
        $totalPhotos = PhotoApproval::count();

        return [
            'total_customers' => $totalCustomers,
            'customers_with_coordinates' => $customersWithCoordinates,
            'customers_without_coordinates' => $customersWithoutCoordinates,
            'coordinate_coverage_rate' => $totalCustomers > 0 ? round(($customersWithCoordinates / $totalCustomers) * 100, 1) : 0,
            'status_counts' => $allStatusCounts,
            'done' => $done,
            'batal' => $allStatusCounts['batal'], // Use status batal count
            'pending_review' => $pendingReview,
            'photo_approved_count' => $photoApproved,
            'total_photos' => $totalPhotos,
            'photo_approval_rate' => $totalPhotos > 0 ? round(($photoApproved / $totalPhotos) * 100, 1) : 0,
            'completion_rate' => $totalActiveCustomers > 0 ? round(($done / $totalActiveCustomers) * 100, 1) : 0, // Base on active customers
        ];
    }

    /**
     * Get filter options for maps
     */
    private function getFilterOptions(): array
    {
        return [
            'kelurahan' => CalonPelanggan::distinct()
                ->whereNotNull('kelurahan')
                ->where('kelurahan', '!=', '')
                ->orderBy('kelurahan')
                ->pluck('kelurahan')
                ->toArray(),
            'padukuhan' => CalonPelanggan::distinct()
                ->whereNotNull('padukuhan')
                ->where('padukuhan', '!=', '')
                ->orderBy('padukuhan')
                ->pluck('padukuhan')
                ->toArray(),
        ];
    }
}
