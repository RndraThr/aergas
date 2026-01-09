<?php

namespace App\Observers;

use App\Models\JalurLineNumber;
use App\Jobs\SyncLineNumberToGoogleSheets;
use Illuminate\Support\Facades\Log;

class JalurLineNumberObserver
{
    /**
     * Handle the JalurLineNumber "created" event.
     */
    public function created(JalurLineNumber $jalurLineNumber): void
    {
        // No need to sync on create as there are no lowering rows yet to update.
    }

    /**
     * Handle the JalurLineNumber "updated" event.
     */
    public function updated(JalurLineNumber $jalurLineNumber): void
    {
        // Check if relevant fields changed
        if ($jalurLineNumber->wasChanged(['nama_jalan', 'actual_mc100'])) {
            $this->triggerSync($jalurLineNumber);
        }
    }

    /**
     * Handle the JalurLineNumber "deleted" event.
     */
    public function deleted(JalurLineNumber $jalurLineNumber): void
    {
        //
    }

    /**
     * Handle the JalurLineNumber "restored" event.
     */
    public function restored(JalurLineNumber $jalurLineNumber): void
    {
        //
    }

    /**
     * Handle the JalurLineNumber "force deleted" event.
     */
    public function forceDeleted(JalurLineNumber $jalurLineNumber): void
    {
        //
    }

    /**
     * Trigger sync job
     */
    private function triggerSync(JalurLineNumber $line)
    {
        if (config('services.google_sheets.enabled') && config('services.google_sheets.sync_mode') === 'realtime') {
            Log::info("Line Number {$line->line_number} updated (MC100/NamaJalan), dispatching sheet update.");

            // Dispatch with data explicitly to avoid serializing issues or race conditions
            SyncLineNumberToGoogleSheets::dispatch(
                $line->line_number,
                $line->nama_jalan ?? '',
                (string) $line->actual_mc100 // Cast to string/null
            );
        }
    }
}
