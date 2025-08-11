<?php

namespace App\Services;

use App\Models\CalonPelanggan;
use App\Models\PhotoApproval;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getCustomerSummaryStats(): array
    {
        $total = CalonPelanggan::count();

        $byStatus = CalonPelanggan::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')->pluck('c','status')->toArray();

        $byProgress = CalonPelanggan::select('progress_status', DB::raw('COUNT(*) as c'))
            ->groupBy('progress_status')->pluck('c','progress_status')->toArray();

        return [
            'total_customers' => $total,
            'by_status'       => $byStatus,
            'by_progress'     => $byProgress,
            'photos_pending_ai'     => PhotoApproval::where('photo_status','ai_pending')->count(),
            'photos_pending_tracer' => PhotoApproval::where('photo_status','tracer_pending')->count(),
            'photos_pending_cgp'    => PhotoApproval::where('photo_status','cgp_pending')->count(),
            'photos_completed'      => PhotoApproval::where('photo_status','cgp_approved')->count(),
        ];
    }

    public function getMonthlyCompletionRate(): float
    {
        $monthStart = now()->startOfMonth();
        $completed = CalonPelanggan::where('progress_status','done')
            ->where('updated_at','>=',$monthStart)->count();

        $total = CalonPelanggan::where('created_at','>=',$monthStart)->count();
        if ($total === 0) return 0.0;

        return round(($completed / $total) * 100, 2);
    }

    public function getAverageCompletionTimeInDays(): float
    {
        $rows = CalonPelanggan::where('progress_status','done')
            ->whereNotNull('tanggal_registrasi')
            ->selectRaw('DATEDIFF(updated_at, tanggal_registrasi) AS d')
            ->pluck('d');

        if ($rows->isEmpty()) return 0.0;
        return round($rows->avg(), 1);
    }
}
