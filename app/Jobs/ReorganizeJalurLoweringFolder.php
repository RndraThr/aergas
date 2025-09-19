<?php

namespace App\Jobs;

use App\Models\JalurLoweringData;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReorganizeJalurLoweringFolder implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected int $loweringId;
    protected string $oldDate;
    protected string $newDate;

    /**
     * Create a new job instance.
     */
    public function __construct(int $loweringId, string $oldDate, string $newDate)
    {
        $this->loweringId = $loweringId;
        $this->oldDate = $oldDate;
        $this->newDate = $newDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $googleDriveService = app(GoogleDriveService::class);

            $result = $googleDriveService->reorganizeJalurLoweringFiles(
                $this->loweringId,
                $this->oldDate,
                $this->newDate
            );

            Log::info('Folder reorganization completed successfully', [
                'lowering_id' => $this->loweringId,
                'old_date' => $this->oldDate,
                'new_date' => $this->newDate,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reorganize folders after date change', [
                'lowering_id' => $this->loweringId,
                'old_date' => $this->oldDate,
                'new_date' => $this->newDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
