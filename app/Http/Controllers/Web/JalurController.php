<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use Illuminate\Http\Request;

use App\Services\JalurExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JalurController extends Controller
{
    private JalurExportService $exportService;

    public function __construct(JalurExportService $exportService)
    {
        $this->exportService = $exportService;
    }

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

        // Calculate comprehensive statistics
        $stats = $this->calculateDashboardStats();

        return view('jalur.dashboard', compact(
            'recentLowering',
            'recentJoint',
            'lineProgress',
            'stats'
        ));
    }

    /**
     * Calculate comprehensive dashboard statistics
     */
    private function calculateDashboardStats()
    {
        $lineNumbers = JalurLineNumber::with(['loweringData', 'cluster'])->get();

        // Overall statistics
        $totalLines = $lineNumbers->count();
        $totalMc0 = $lineNumbers->sum('estimasi_panjang');
        $totalActualLowering = $lineNumbers->sum('total_penggelaran');
        $totalMc100 = $lineNumbers->sum('actual_mc100');

        // Progress calculation
        $completedLines = $lineNumbers->where('status_line', 'completed')->count();
        $inProgressLines = $lineNumbers->where('status_line', 'in_progress')->count();
        $notStartedLines = $lineNumbers->where('status_line', 'pending')->count();

        $overallProgress = $totalMc0 > 0 ? round(($totalActualLowering / $totalMc0) * 100, 1) : 0;

        // Diameter breakdown
        $diameterStats = [
            '63' => $this->getDiameterStats($lineNumbers, '63'),
            '90' => $this->getDiameterStats($lineNumbers, '90'),
            '180' => $this->getDiameterStats($lineNumbers, '180'),
        ];

        // Fitting statistics from Joint data
        $fittingStats = $this->getFittingStats();

        // Lowering statistics
        $loweringStats = $this->getLoweringStats();

        return [
            'total_lines' => $totalLines,
            'total_mc0' => $totalMc0,
            'total_actual_lowering' => $totalActualLowering,
            'total_mc100' => $totalMc100,
            'completed_lines' => $completedLines,
            'in_progress_lines' => $inProgressLines,
            'not_started_lines' => $notStartedLines,
            'overall_progress' => $overallProgress,
            'diameter_stats' => $diameterStats,
            'fitting_stats' => $fittingStats,
            'lowering_stats' => $loweringStats,
        ];
    }

    /**
     * Get statistics for specific diameter
     */
    private function getDiameterStats($lineNumbers, $diameter)
    {
        $lines = $lineNumbers->where('diameter', $diameter);
        $totalPanjang = $lines->sum('total_penggelaran');
        $estimasi = $lines->sum('estimasi_panjang');
        $progress = $estimasi > 0 ? round(($totalPanjang / $estimasi) * 100, 1) : 0;

        return [
            'count' => $lines->count(),
            'total_panjang' => $totalPanjang,
            'estimasi' => $estimasi,
            'progress' => $progress,
            'completed' => $lines->where('status_line', 'completed')->count(),
            'in_progress' => $lines->where('status_line', 'in_progress')->count(),
        ];
    }

    /**
     * Get fitting statistics from Joint data
     */
    private function getFittingStats()
    {
        $joints = JalurJointData::with('fittingType')->get();

        $fittingsByType = $joints->groupBy('fitting_type_id')->map(function ($group) {
            $byDiameter = $group->groupBy(function ($joint) {
                // Extract diameter from line number string
                if (preg_match('/^(\d+)-/', $joint->joint_line_from ?? '', $matches)) {
                    return $matches[1];
                }
                return 'unknown';
            });

            return [
                'name' => $group->first()->fittingType?->nama_fitting ?? 'Unknown',
                'total' => $group->count(),
                'by_diameter' => [
                    '63' => ($byDiameter['63'] ?? collect())->count(),
                    '90' => ($byDiameter['90'] ?? collect())->count(),
                    '180' => ($byDiameter['180'] ?? collect())->count(),
                ]
            ];
        });

        $totalFittings = $joints->count();

        return [
            'total' => $totalFittings,
            'by_type' => $fittingsByType,
        ];
    }

    /**
     * Get lowering statistics
     */
    private function getLoweringStats()
    {
        $lowering = JalurLoweringData::all();

        return [
            'total_entries' => $lowering->count(),
            'approved' => $lowering->where('status_cgp', 'acc_cgp')->count(),
            'pending_cgp' => $lowering->where('status_cgp', 'waiting')->count(),
            'pending_tracer' => $lowering->where('status_tracer', 'waiting')->count(),
            'rejected' => $lowering->where('status_cgp', 'rejected')->count(),
            'avg_length' => $lowering->avg('panjang_penggelaran_pipa') ?? 0,
            'total_photos' => $lowering->sum(function ($l) {
                return ($l->foto_evidence_1 ? 1 : 0) + ($l->foto_evidence_2 ? 1 : 0);
            }),
        ];
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
            'loweringData.photoApprovals' // Load photos for export
        ])->get();

        if ($export === 'excel') {
            // Eager load relations for jointData for performance optimization
            // Since jointData is accessed via accessor which runs a query, we iterate to load relations on the resulting collection
            foreach ($lineNumbers as $line) {
                // Access jointData to trigger the query, then load its relations
                $line->jointData->load(['fittingType', 'photoApprovals']);
            }
            return $this->exportComprehensiveToExcel($lineNumbers);
        }

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

        return response()->json([
            'data' => $data,
            'totals' => $totals
        ]);
    }

    private function exportComprehensiveToExcel($lineNumbers)
    {
        $spreadsheet = $this->exportService->generateSpreadsheet($lineNumbers);
        $filename = 'Laporan_Lengkap_Jalur_' . date('Y_m_d_His') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}