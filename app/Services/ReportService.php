<?php

namespace App\Services;

use App\Models\CalonPelanggan;

class ReportService
{
    /**
     * Get customer statistics summary
     *
     * @return array
     */
    public function getCustomerSummaryStats(): array
    {
        return [
            'total_customers' => CalonPelanggan::count(),
            'active_customers' => CalonPelanggan::whereIn('status', ['validated', 'in_progress'])->count(),
            'completed_customers' => CalonPelanggan::where('progress_status', 'done')->count(),
            'cancelled_customers' => CalonPelanggan::where('status', 'batal')->count(),
            'pending_validation' => CalonPelanggan::where('status', 'pending')->count(),
        ];
    }

    /**
     * Get monthly completion rate for customers
     *
     * @return float
     */
    public function getMonthlyCompletionRate(): float
    {
        $startersThisMonth = CalonPelanggan::whereMonth('tanggal_registrasi', now()->month)
                                           ->whereYear('tanggal_registregistrasi', now()->year)
                                           ->count();

        if ($startersThisMonth === 0) {
            return 0;
        }

        $completedThisMonth = CalonPelanggan::whereMonth('tanggal_registrasi', now()->month)
                                            ->whereYear('tanggal_registrasi', now()->year)
                                            ->where('progress_status', 'done')
                                            ->count();

        return round(($completedThisMonth / $startersThisMonth) * 100, 2);
    }

    /**
     * Get average completion time in days for customers
     *
     * @return float
     */
    public function getAverageCompletionTimeInDays(): float
    {
        $completedCustomers = CalonPelanggan::where('progress_status', 'done')
                                            ->whereNotNull('tanggal_registrasi')
                                            ->selectRaw('DATEDIFF(updated_at, tanggal_registrasi) as completion_days')
                                            ->get();

        if ($completedCustomers->isEmpty()) {
            return 0;
        }

        return round($completedCustomers->avg('completion_days'), 1);
    }
}
