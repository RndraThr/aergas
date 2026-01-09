<?php

namespace App\Jobs;

use App\Models\JalurLoweringData;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLoweringToGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $lowering;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(JalurLoweringData $lowering)
    {
        $this->lowering = $lowering;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetsService $sheetsService): void
    {
        try {
            Log::info("Syncing lowering data to Google Sheets", [
                'lowering_id' => $this->lowering->id,
                'line_number' => $this->lowering->lineNumber->line_number ?? 'N/A'
            ]);

            $success = $sheetsService->syncLowering($this->lowering);

            if ($success) {
                // Update sync status in database (optional)
                $this->lowering->update([
                    'synced_to_sheets_at' => now()
                ]);

                Log::info("Successfully synced lowering to Google Sheets", [
                    'lowering_id' => $this->lowering->id
                ]);
            } else {
                throw new \Exception("Failed to sync lowering data");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing lowering to Google Sheets", [
                'lowering_id' => $this->lowering->id,
                'error' => $e->getMessage()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to sync lowering after {$this->tries} attempts", [
            'lowering_id' => $this->lowering->id,
            'error' => $exception->getMessage()
        ]);

        // Notify admin or log to monitoring system
        // You can add notification here if needed
    }
}
