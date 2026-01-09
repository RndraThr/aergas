<?php

namespace App\Jobs;

use App\Models\JalurJointData;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJointToGoogleSheets implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $jointId;

    /**
     * Create a new job instance.
     * @param int|null $jointId
     */
    public function __construct($jointId = null)
    {
        $this->jointId = $jointId;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId()
    {
        return $this->jointId;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetsService $sheetsService): void
    {
        try {
            // 1. Sync Specific Joint Row if ID provided
            if ($this->jointId) {
                // Eager load necessary relations
                $joint = JalurJointData::with(['lineNumber', 'fittingType'])->find($this->jointId);
                if ($joint) {
                    Log::info("Syncing Joint Row for Joint ID: {$this->jointId}");
                    $sheetsService->syncJointRow($joint);
                }
            }

            // 2. Always Sync Joint Summary Table
            Log::info("Syncing Joint Data Summary...");
            $sheetsService->syncJointSummary();

        } catch (\Exception $e) {
            Log::error("Error syncing Joint Data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to sync Joint Data after {$this->tries} attempts: " . $exception->getMessage());
    }
}
