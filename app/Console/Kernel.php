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
        // Daily report at 8:00 AM Jakarta time
        $schedule->command('aergas:daily-report')
                 ->dailyAt('08:00')
                 ->timezone('Asia/Jakarta');

        // SLA check every hour during working hours
        $schedule->command('aergas:check-sla')
                 ->hourly()
                 ->between('06:00', '22:00')
                 ->timezone('Asia/Jakarta');

        // Weekly summary report (Mondays at 9:00 AM)
        $schedule->command('aergas:weekly-report')
                 ->weeklyOn(1, '09:00')
                 ->timezone('Asia/Jakarta');

        // Cleanup tasks
        $schedule->command('sanctum:prune-expired --hours=24')->daily();

        $schedule->call(function () {
            \App\Models\AuditLog::where('created_at', '<', now()->subDays(90))->delete();
        })->daily()->at('03:00');

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
