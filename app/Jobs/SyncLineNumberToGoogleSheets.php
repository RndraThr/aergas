<?php

namespace App\Jobs;

use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLineNumberToGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lineNumber;
    protected $namaJalan;
    protected $mc100;

    public $tries = 3;
    public $timeout = 180; // Longer timeout due to search operation

    /**
     * Create a new job instance.
     */
    public function __construct(string $lineNumber, string $namaJalan, ?string $mc100)
    {
        $this->lineNumber = $lineNumber;
        $this->namaJalan = $namaJalan;
        $this->mc100 = $mc100 ?? '';
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetsService $sheetsService): void
    {
        try {
            Log::info("Syncing Line Number Changes to Sheets for: {$this->lineNumber}");

            $success = $sheetsService->updateLineNumberData(
                $this->lineNumber,
                $this->namaJalan,
                $this->mc100
            );

            if ($success) {
                Log::info("Successfully synced Line Number changes for: {$this->lineNumber}");
            } else {
                throw new \Exception("Failed to sync Line Number changes.");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing Line Number to Sheets: " . $e->getMessage());
            throw $e;
        }
    }
}
