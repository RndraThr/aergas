<?php

namespace App\Observers;

use App\Models\PhotoApproval;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use App\Jobs\SyncLoweringToGoogleSheets;
use App\Jobs\SyncJointToGoogleSheets;
use Illuminate\Support\Facades\Log;

class PhotoApprovalObserver
{
    /**
     * Handle the PhotoApproval "created" event.
     */
    public function created(PhotoApproval $photo): void
    {
        $this->handleSync($photo, 'created');
    }

    /**
     * Handle the PhotoApproval "updated" event.
     */
    public function updated(PhotoApproval $photo): void
    {
        $this->handleSync($photo, 'updated');
    }

    /**
     * Handle the PhotoApproval "deleted" event.
     */
    public function deleted(PhotoApproval $photo): void
    {
        $this->handleSync($photo, 'deleted');
    }

    /**
     * Common sync logic
     */
    protected function handleSync(PhotoApproval $photo, string $event)
    {
        if (!config('services.google_sheets.enabled'))
            return;
        if (config('services.google_sheets.sync_mode') !== 'realtime')
            return;

        try {
            if ($photo->module_name === 'jalur_lowering' && $photo->module_record_id) {
                $lowering = JalurLoweringData::find($photo->module_record_id);
                if ($lowering) {
                    Log::info("PhotoApproval {$event} for Lowering {$lowering->id}, triggering Sheet Sync.");
                    // Delay 10 seconds to allow initial sync (append) to resolve and appear in Sheet API read
                    SyncLoweringToGoogleSheets::dispatch($lowering);
                }
            } elseif ($photo->module_name === 'jalur_joint' && $photo->module_record_id) {
                $joint = JalurJointData::find($photo->module_record_id);
                if ($joint) {
                    Log::info("PhotoApproval {$event} for Joint {$joint->id}, triggering Sheet Sync.");
                    SyncJointToGoogleSheets::dispatch($joint->id);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in PhotoApprovalObserver: " . $e->getMessage());
        }
    }
}
