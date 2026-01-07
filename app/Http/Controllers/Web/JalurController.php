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
            'lineNumbers' => function ($q) {
                $q->with(['loweringData']);
            }
        ])->active()->get();

        $data = $clusters->map(function ($cluster) {
            $totalLines = $cluster->lineNumbers->count();
            // Calculate how many lines have meaningful progress (penggelaran > 0)
            $linesWithProgress = $cluster->lineNumbers->where('total_penggelaran', '>', 0)->count();

            $totalEstimate = $cluster->lineNumbers->sum('estimasi_panjang');
            // Calculate actual from both actual_mc100 field and total penggelaran from lowering data
            $totalActualFromField = $cluster->lineNumbers->whereNotNull('actual_mc100')->sum('actual_mc100');
            $totalPenggelaran = $cluster->lineNumbers->sum('total_penggelaran');

            // Use actual_mc100 if available, otherwise use total_penggelaran as actual
            $totalActual = $totalActualFromField > 0 ? $totalActualFromField : $totalPenggelaran;

            // Calculate progress based on actual work done vs estimate
            $progressPercentage = 0;
            if ($totalEstimate > 0) {
                $progressPercentage = min(100, ($totalPenggelaran / $totalEstimate) * 100);
            }

            // Only calculate variance if we have actual data
            $variance = ($totalActual > 0 && $totalEstimate > 0) ? $totalActual - $totalEstimate : null;

            return [
                'cluster' => $cluster->nama_cluster,
                'code' => $cluster->code_cluster,
                'total_lines' => $totalLines,
                'completed_lines' => $linesWithProgress, // Use lines with progress instead of status
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
            ->map(function ($line) {
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
            ->where(function ($q) {
                $q->whereNotNull('actual_mc100')
                    ->orWhere('total_penggelaran', '>', 0);
            })
            ->get()
            ->map(function ($line) {
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
            'loweringData'
        ])->get();

        $data = $lineNumbers->map(function ($line) {
            // Calculate lowering totals
            $loweringEntries = $line->loweringData->count();
            $totalPenggelaran = $line->total_penggelaran ?? 0;
            $actualMc100 = $line->actual_mc100 ?? 0;
            $loweringLastUpdate = $line->loweringData->max('updated_at');

            // Calculate lowering approval status
            $loweringAccCgp = $line->loweringData->where('status_laporan', 'acc_cgp')->count();
            $loweringAccTracer = $line->loweringData->where('status_laporan', 'acc_tracer')->count();
            $loweringRevisi = $line->loweringData->whereIn('status_laporan', ['revisi_tracer', 'revisi_cgp'])->count();
            $loweringDraft = $line->loweringData->where('status_laporan', 'draft')->count();

            // Calculate joint totals - use accessor (no eager loading possible)
            $joints = $line->jointData; // This calls the accessor which queries and caches
            $jointTotal = $joints->count();
            $jointAccCgp = $joints->where('status_laporan', 'acc_cgp')->count();
            $jointAccTracer = $joints->where('status_laporan', 'acc_tracer')->count();
            $jointRevisi = $joints->whereIn('status_laporan', ['revisi_tracer', 'revisi_cgp'])->count();
            $jointDraft = $joints->where('status_laporan', 'draft')->count();
            $jointLastUpdate = $joints->max('updated_at');

            // Calculate variance
            $estimasi = $line->estimasi_panjang ?? 0;
            $variance = $actualMc100 > 0 ? ($actualMc100 - $estimasi) : null;
            $variancePercentage = ($estimasi > 0 && $variance !== null) ? (($variance / $estimasi) * 100) : null;

            // Calculate progress
            $progressPercentage = $estimasi > 0 ? (($totalPenggelaran / $estimasi) * 100) : 0;

            // Determine overall status based on approval statuses
            $totalRecords = $loweringEntries + $jointTotal;
            $totalAccCgp = $loweringAccCgp + $jointAccCgp;
            $totalRevisi = $loweringRevisi + $jointRevisi;
            $totalAccTracer = $loweringAccTracer + $jointAccTracer;
            $totalDraft = $loweringDraft + $jointDraft;

            if ($totalRecords === 0) {
                // No data yet
                $overallStatus = 'pending';
            } elseif ($totalRevisi > 0) {
                // Ada yang butuh revisi (prioritas tertinggi)
                $overallStatus = 'needs_revision';
            } elseif ($totalAccCgp === $totalRecords) {
                // Semua sudah ACC CGP
                $overallStatus = 'completed';
            } elseif ($totalAccCgp > 0 || $totalAccTracer > 0) {
                // Ada yang sudah ACC (tracer atau CGP) tapi belum semua
                $overallStatus = 'in_progress';
            } elseif ($totalDraft > 0) {
                // Semua masih draft
                $overallStatus = 'in_progress';
            } else {
                $overallStatus = 'pending';
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
                'joint_completed' => $jointAccCgp,
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