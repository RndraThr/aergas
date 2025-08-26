<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Essential cleanup tasks only
        $schedule->command('sanctum:prune-expired --hours=24')->daily();

        // Cleanup old audit logs (keep 90 days)
        $schedule->call(function () {
            \App\Models\AuditLog::where('created_at', '<', now()->subDays(90))->delete();
        })->daily()->at('03:00');

        // Cleanup old notifications (keep 30 days)
        $schedule->call(function () {
            \App\Models\Notification::where('created_at', '<', now()->subDays(30))->delete();
        })->daily()->at('03:30');

        // Refresh Google Drive token setiap 6 jam (lebih aman)
        $schedule->command('google-drive:keep-alive')
                ->everySixHours()
                ->withoutOverlapping()
                ->onFailure(function () {
                    // Kirim alert ke admin jika gagal
                    Log::emergency('Google Drive token refresh failed in scheduler');
                });

        // Backup refresh setiap hari
        $schedule->command('google-drive:refresh-token')
                ->daily()
                ->at('01:00')
                ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
