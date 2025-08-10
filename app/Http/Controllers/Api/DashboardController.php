<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalonPelanggan;
use App\Models\PhotoApproval;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\MgrtData;
use App\Models\GasInData;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Base statistics
        $stats = [
            'total_customers' => CalonPelanggan::count(),
            'active_customers' => CalonPelanggan::whereIn('status', ['validated', 'in_progress'])->count(),
            'completed_customers' => CalonPelanggan::where('progress_status', 'done')->count(),
            'cancelled_customers' => CalonPelanggan::where('status', 'batal')->count(),
        ];

        // Progress status breakdown
        $progressStats = CalonPelanggan::selectRaw('progress_status, COUNT(*) as count')
                                      ->groupBy('progress_status')
                                      ->pluck('count', 'progress_status')
                                      ->toArray();

        // Module completion stats
        $moduleStats = [
            'sk_completed' => SkData::where('module_status', 'completed')->count(),
            'sr_completed' => SrData::where('module_status', 'completed')->count(),
            'mgrt_completed' => MgrtData::where('module_status', 'completed')->count(),
            'gas_in_completed' => GasInData::where('module_status', 'completed')->count(),
        ];

        // Photo approval stats
        $photoStats = [
            'pending_ai' => PhotoApproval::where('photo_status', 'ai_pending')->count(),
            'pending_tracer' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
            'pending_cgp' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'completed_today' => PhotoApproval::where('photo_status', 'cgp_approved')
                                             ->whereDate('cgp_approved_at', today())->count(),
        ];

        // Role-specific data
        $roleSpecificData = $this->getRoleSpecificData($user);

        // Recent activities (last 10)
        $recentActivities = $this->getRecentActivities($user, 10);

        // Notifications count
        $notificationCount = Notification::where('user_id', $user->id)
                                        ->where('is_read', false)
                                        ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'progress_stats' => $progressStats,
                'module_stats' => $moduleStats,
                'photo_stats' => $photoStats,
                'role_specific' => $roleSpecificData,
                'recent_activities' => $recentActivities,
                'notification_count' => $notificationCount,
                'user' => [
                    'name' => $user->name,
                    'role' => $user->role,
                    'last_login' => $user->last_login
                ]
            ]
        ]);
    }

    public function getChartData(Request $request)
    {
        $period = $request->get('period', '7days'); // 7days, 30days, 90days

        $endDate = Carbon::now();
        $startDate = match($period) {
            '7days' => $endDate->copy()->subDays(7),
            '30days' => $endDate->copy()->subDays(30),
            '90days' => $endDate->copy()->subDays(90),
            default => $endDate->copy()->subDays(7)
        };

        // Daily completion chart
        $completionChart = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $completionChart[] = [
                'date' => $current->format('Y-m-d'),
                'sk_completed' => SkData::where('module_status', 'completed')
                                       ->whereDate('cgp_approved_at', $current)
                                       ->count(),
                'sr_completed' => SrData::where('module_status', 'completed')
                                       ->whereDate('cgp_approved_at', $current)
                                       ->count(),
                'gas_in_completed' => GasInData::where('module_status', 'completed')
                                              ->whereDate('cgp_approved_at', $current)
                                              ->count(),
            ];
            $current->addDay();
        }

        // Photo approval trend
        $photoTrend = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $photoTrend[] = [
                'date' => $current->format('Y-m-d'),
                'ai_approved' => PhotoApproval::where('photo_status', 'ai_approved')
                                             ->whereDate('ai_approved_at', $current)
                                             ->count(),
                'tracer_approved' => PhotoApproval::where('photo_status', 'tracer_approved')
                                                 ->whereDate('tracer_approved_at', $current)
                                                 ->count(),
                'cgp_approved' => PhotoApproval::where('photo_status', 'cgp_approved')
                                              ->whereDate('cgp_approved_at', $current)
                                              ->count(),
            ];
            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'completion_chart' => $completionChart,
                'photo_trend' => $photoTrend,
                'period' => $period
            ]
        ]);
    }

    private function getRoleSpecificData($user)
    {
        switch ($user->role) {
            case 'tracer':
                return [
                    'pending_reviews' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
                    'reviewed_today' => PhotoApproval::where('photo_status', 'tracer_approved')
                                                    ->whereDate('tracer_approved_at', today())
                                                    ->count(),
                    'customers_pending' => CalonPelanggan::where('status', 'pending')->count(),
                ];

            case 'admin':
                return [
                    'pending_cgp_review' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
                    'cgp_reviewed_today' => PhotoApproval::where('photo_status', 'cgp_approved')
                                                        ->whereDate('cgp_approved_at', today())
                                                        ->count(),
                    'system_alerts' => Notification::where('type', 'system_alert')
                                                  ->where('is_read', false)
                                                  ->count(),
                ];

            case 'sk':
                return [
                    'my_assignments' => SkData::where('nama_petugas_sk', $user->full_name)
                                             ->whereIn('module_status', ['draft', 'ai_validation'])
                                             ->count(),
                    'completed_this_month' => SkData::where('nama_petugas_sk', $user->full_name)
                                                   ->where('module_status', 'completed')
                                                   ->whereMonth('cgp_approved_at', now()->month)
                                                   ->count(),
                    'rejected_photos' => PhotoApproval::whereHas('pelanggan.skData', function($q) use ($user) {
                                                      $q->where('nama_petugas_sk', $user->full_name);
                                                  })
                                                  ->whereIn('photo_status', ['ai_rejected', 'tracer_rejected', 'cgp_rejected'])
                                                  ->count(),
                ];

            default:
                return [];
        }
    }

    private function getRecentActivities($user, $limit = 10)
    {
        // This would typically come from audit logs
        // For now, return recent photo approvals and module completions

        $activities = [];

        // Recent photo approvals
        if ($user->isTracer() || $user->isAdmin()) {
            $recentApprovals = PhotoApproval::with('pelanggan')
                                           ->where('photo_status', '!=', 'draft')
                                           ->orderBy('updated_at', 'desc')
                                           ->take($limit / 2)
                                           ->get();

            foreach ($recentApprovals as $approval) {
                $activities[] = [
                    'type' => 'photo_approval',
                    'message' => "Photo {$approval->photo_field_name} for {$approval->pelanggan->nama_pelanggan} - {$approval->photo_status}",
                    'timestamp' => $approval->updated_at,
                    'reff_id' => $approval->reff_id_pelanggan
                ];
            }
        }

        // Recent module completions
        $recentCompletions = SkData::with('pelanggan')
                                  ->where('module_status', 'completed')
                                  ->orderBy('cgp_approved_at', 'desc')
                                  ->take($limit / 2)
                                  ->get();

        foreach ($recentCompletions as $completion) {
            $activities[] = [
                'type' => 'module_completion',
                'message' => "SK module completed for {$completion->pelanggan->nama_pelanggan}",
                'timestamp' => $completion->cgp_approved_at,
                'reff_id' => $completion->reff_id_pelanggan
            ];
        }

        // Sort by timestamp and limit
        return collect($activities)->sortByDesc('timestamp')->take($limit)->values();
    }
}
