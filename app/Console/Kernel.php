<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
