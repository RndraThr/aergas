<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleSyncRunnerService;
use App\Services\GoogleSheetSettingsService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSyncGoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheets:auto-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Background task to automatically sync system Recap data to Google Sheets based on JSON user settings.';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSyncRunnerService $runner, GoogleSheetSettingsService $settingsService)
    {
        $settings = $settingsService->getSettings();
        $this->info("Settings Loaded: " . json_encode($settings));
        \Log::info("Settings Loaded: ", $settings);

        // 1. Check if Enabled
        if (empty($settings['auto_sync_enabled']) || $settings['auto_sync_enabled'] == false || empty($settings['spreadsheet_id']) || empty($settings['sheet_name'])) {
            $this->info("Bypassed: Disabled or Missing Configs");
            \Log::info("Bypassed: Disabled or Missing Configs");
            return 0; // Silently skip
        }

        // 2. Check if Interval has passed
        $lastSynced = $settings['last_synced_at'] ? Carbon::parse($settings['last_synced_at']) : null;
        $intervalMinutes = (int) ($settings['sync_interval_minutes'] ?? 60);

        if ($lastSynced && $lastSynced->copy()->addMinutes($intervalMinutes)->isFuture()) {
            $this->info("Bypassed: Interval timeout wait");
            return 0; // Skip, not enough time has passed
        }

        $this->info("Starting auto-sync to Sheets...");

        try {
            // Extracted runner
            $startRow = (int) ($settings['start_row'] ?? 5);
            $totalSynced = $runner->execute($settings['spreadsheet_id'], $settings['sheet_name'], $startRow);

            // Mark Success
            $settingsService->markSyncSuccess();
            $this->info("Successfully synced $totalSynced rows.");
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Auto-sync failed', ['error' => $e->getMessage()]);
            // Mark Fail
            $settingsService->markSyncFailed($e->getMessage());
            $this->error('Merge failed: ' . $e->getMessage());
            
            return 1;
        }
    }
}
