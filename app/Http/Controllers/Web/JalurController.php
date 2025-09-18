<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use Illuminate\Http\Request;

class JalurController extends Controller
{
    public function index()
    {
        $stats = [
            'total_clusters' => JalurCluster::active()->count(),
            'total_line_numbers' => JalurLineNumber::active()->count(),
            'total_lowering' => JalurLoweringData::count(),
            'total_joint' => JalurJointData::count(),
            'completed_lines' => JalurLineNumber::completed()->count(),
        ];

        return view('jalur.index', compact('stats'));
    }

    public function dashboard()
    {
        $recentLowering = JalurLoweringData::with(['lineNumber.cluster'])
            ->latest()
            ->limit(5)
            ->get();

        $recentJoint = JalurJointData::with(['cluster', 'fittingType'])
            ->latest()
            ->limit(5)
            ->get();

        $lineProgress = JalurLineNumber::with('cluster')
            ->where('status_line', 'in_progress')
            ->limit(10)
            ->get();

        return view('jalur.dashboard', compact('recentLowering', 'recentJoint', 'lineProgress'));
    }

    public function reports()
    {
        return view('jalur.reports');
    }

    public function getReportData(Request $request)
    {
        $type = $request->get('type', 'summary');
        $export = $request->get('export', false);
        
        switch ($type) {
            case 'summary':
                return $this->getSummaryReport();
            case 'progress':
                return $this->getProgressReport();
            case 'variance':
                return $this->getVarianceReport();
            case 'comprehensive':
                return $this->getComprehensiveReport($export);
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }
    }

    private function getSummaryReport()
    {
        $clusters = JalurCluster::with([
            'lineNumbers' => function($q) {
                $q->with(['loweringData']);
            }
        ])->active()->get();

        $data = $clusters->map(function($cluster) {
            $totalLines = $cluster->lineNumbers->count();
            $completedLines = $cluster->lineNumbers->where('status_line', 'completed')->count();
            $totalEstimate = $cluster->lineNumbers->sum('estimasi_panjang');
            // Calculate actual from both actual_mc100 field and total penggelaran from lowering data
            $totalActualFromField = $cluster->lineNumbers->whereNotNull('actual_mc100')->sum('actual_mc100');
            $totalPenggelaran = $cluster->lineNumbers->sum('total_penggelaran');

            // Use actual_mc100 if available, otherwise use total_penggelaran as actual
            $totalActual = $totalActualFromField > 0 ? $totalActualFromField : $totalPenggelaran;

            // Calculate progress based on actual work done vs estimate, not just completed count
            $progressPercentage = 0;
            if ($totalEstimate > 0) {
                $progressPercentage = min(100, ($totalPenggelaran / $totalEstimate) * 100);
            }

            // Calculate how many lines have meaningful progress (penggelaran > 0)
            $linesWithProgress = $cluster->lineNumbers->where('total_penggelaran', '>', 0)->count();

            // Only calculate variance if we have actual data
            $variance = ($totalActual > 0 && $totalEstimate > 0) ? $totalActual - $totalEstimate : null;

            return [
                'cluster' => $cluster->nama_cluster,
                'code' => $cluster->code_cluster,
                'total_lines' => $totalLines,
                'completed_lines' => $completedLines,
                'lines_with_progress' => $linesWithProgress,
                'progress_percentage' => round($progressPercentage, 1),
                'total_estimate' => $totalEstimate,
                'total_penggelaran' => $totalPenggelaran,
                'total_actual' => $totalActual,
                'variance' => $variance,
            ];
        });

        return response()->json($data);
    }

    private function getProgressReport()
    {
        $lineNumbers = JalurLineNumber::with(['cluster', 'loweringData'])
            ->get()
            ->map(function($line) {
                return [
                    'line_number' => $line->line_number,
                    'cluster' => $line->cluster->nama_cluster,
                    'diameter' => $line->diameter,
                    'estimasi_panjang' => $line->estimasi_panjang,
                    'total_penggelaran' => $line->total_penggelaran,
                    'actual_mc100' => $line->actual_mc100,
                    'status' => $line->status_line,
                    'progress_percentage' => $line->getProgressPercentage(),
                    'variance_percentage' => $line->getVariancePercentage(),
                    'lowering_count' => $line->loweringData->count(),
                ];
            });

        return response()->json($lineNumbers);
    }

    private function getVarianceReport()
    {
        $lineNumbers = JalurLineNumber::with('cluster')
            ->where(function($q) {
                $q->whereNotNull('actual_mc100')
                  ->orWhere('total_penggelaran', '>', 0);
            })
            ->get()
            ->map(function($line) {
                // Use actual_mc100 if available, otherwise use total_penggelaran
                $actualValue = $line->actual_mc100 ?? $line->total_penggelaran;
                
                if (!$actualValue || $line->estimasi_panjang <= 0) {
                    return null; // Skip lines without valid data
                }
                
                $variance = $actualValue - $line->estimasi_panjang;
                $variancePercentage = ($variance / $line->estimasi_panjang) * 100;
                
                return [
                    'line_number' => $line->line_number,
                    'cluster' => $line->cluster->nama_cluster,
                    'estimasi_panjang' => $line->estimasi_panjang,
                    'actual_mc100' => $actualValue,
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'status' => $variancePercentage > 10 ? 'over' : 
                              ($variancePercentage < -10 ? 'under' : 'normal'),
                ];
            })
            ->filter() // Remove null values
            ->values(); // Reset array keys

        return response()->json($lineNumbers);
    }

    private function getComprehensiveReport($export = false)
    {
        $lineNumbers = JalurLineNumber::with([
            'cluster', 
            'loweringData', 
            'jointData' => function($q) {
                $q->with('fittingType');
            }
        ])->get();

        $data = $lineNumbers->map(function($line) {
            // Calculate lowering totals
            $loweringEntries = $line->loweringData->count();
            $totalPenggelaran = $line->total_penggelaran ?? 0;
            $actualMc100 = $line->actual_mc100 ?? 0;
            $loweringLastUpdate = $line->loweringData->max('updated_at');

            // Calculate joint totals
            $jointTotal = $line->jointData->count();
            $jointCompleted = $line->jointData->where('status_laporan', 'cgp_approved')->count();
            $jointLastUpdate = $line->jointData->max('updated_at');

            // Calculate variance
            $estimasi = $line->estimasi_panjang ?? 0;
            $variance = $actualMc100 > 0 ? ($actualMc100 - $estimasi) : null;
            $variancePercentage = ($estimasi > 0 && $variance !== null) ? (($variance / $estimasi) * 100) : null;

            // Calculate progress
            $progressPercentage = $estimasi > 0 ? (($totalPenggelaran / $estimasi) * 100) : 0;

            // Determine overall status
            $overallStatus = 'pending';
            if ($jointCompleted > 0 && $actualMc100 > 0) {
                $overallStatus = 'completed';
            } elseif ($loweringEntries > 0 || $jointTotal > 0) {
                $overallStatus = 'in_progress';
            }

            return [
                'line_number' => $line->line_number,
                'cluster' => $line->cluster->nama_cluster,
                'cluster_code' => $line->cluster->code_cluster,
                'diameter' => $line->diameter,
                'nama_jalan' => $line->nama_jalan,
                'estimasi_panjang' => $estimasi,
                'is_active' => $line->is_active,
                
                // Lowering data
                'lowering_entries' => $loweringEntries,
                'total_penggelaran' => $totalPenggelaran,
                'actual_mc100' => $actualMc100,
                'lowering_last_update' => $loweringLastUpdate,
                
                // Joint data
                'joint_total' => $jointTotal,
                'joint_completed' => $jointCompleted,
                'joint_last_update' => $jointLastUpdate,
                
                // Calculations
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'progress_percentage' => min(100, $progressPercentage),
                'overall_status' => $overallStatus,
            ];
        });

        // Calculate totals
        $totals = [
            'total_lines' => $lineNumbers->count(),
            'total_estimasi' => $lineNumbers->sum('estimasi_panjang'),
            'total_penggelaran' => $lineNumbers->sum('total_penggelaran'),
            'total_mc100' => $lineNumbers->sum('actual_mc100'),
        ];

        if ($export === 'excel') {
            return $this->exportComprehensiveToExcel($data, $totals);
        }

        return response()->json([
            'data' => $data,
            'totals' => $totals
        ]);
    }

    private function exportComprehensiveToExcel($data, $totals)
    {
        // For now, return JSON with export flag
        // Later can implement actual Excel export using Laravel Excel
        return response()->json([
            'data' => $data,
            'totals' => $totals,
            'export_type' => 'excel',
            'filename' => 'laporan_lengkap_jalur_' . date('Y_m_d') . '.json'
        ]);
    }
}