<?php

namespace App\Observers;

use App\Models\JalurJointData;
use App\Jobs\SyncJointToGoogleSheets;
use Illuminate\Support\Facades\Log;

class JalurJointDataObserver
{
    /**
     * Handle the JalurJointData "created" event.
     */
    public function created(JalurJointData $jalurJointData): void
    {
        $this->triggerSync('created', $jalurJointData);
    }

    /**
     * Handle the JalurJointData "updated" event.
     */
    public function updated(JalurJointData $jalurJointData): void
    {
        $this->triggerSync('updated', $jalurJointData);
    }

    /**
     * Handle the JalurJointData "deleted" event.
     */
    public function deleted(JalurJointData $jalurJointData): void
    {
        $this->triggerSync('deleted', $jalurJointData);
    }

    /**
     * Handle the JalurJointData "restored" event.
     */
    public function restored(JalurJointData $jalurJointData): void
    {
        $this->triggerSync('restored', $jalurJointData);
    }

    /**
     * Handle the JalurJointData "force deleted" event.
     */
    public function forceDeleted(JalurJointData $jalurJointData): void
    {
        $this->triggerSync('force_deleted', $jalurJointData);
    }

    /**
     * Trigger sync job if enabled
     */
    private function triggerSync(string $event, JalurJointData $data)
    {
        if (config('services.google_sheets.enabled') && config('services.google_sheets.sync_mode') === 'realtime') {
            Log::info("Joint Data {$event}, dispatching summary sync", ['id' => $data->id]);
            SyncJointToGoogleSheets::dispatch($data->id);
        }
    }
}
