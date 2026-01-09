<?php

namespace App\Observers;

use App\Models\JalurLoweringData;
use App\Jobs\SyncLoweringToGoogleSheets;
use Illuminate\Support\Facades\Log;

class JalurLoweringDataObserver
{
    /**
     * Handle the JalurLoweringData "created" event.
     */
    public function created(JalurLoweringData $jalurLoweringData): void
    {
        // Only sync if Google Sheets is enabled
        if (config('services.google_sheets.enabled') && config('services.google_sheets.sync_mode') === 'realtime') {
            Log::info('Dispatching Google Sheets sync job for new lowering data', [
                'lowering_id' => $jalurLoweringData->id
            ]);

            // Dispatch logic disabled to prevent double-sync (handled by PhotoApprovalObserver & Controller fallback)
        }
    }

    /**
     * Handle the JalurLoweringData "updated" event.
     */
    public function updated(JalurLoweringData $jalurLoweringData): void
    {
        // Only sync if Google Sheets is enabled and significant fields changed
        if (config('services.google_sheets.enabled') && config('services.google_sheets.sync_mode') === 'realtime') {
            // Check if important fields were modified
            $importantFields = [
                'tanggal_jalur',
                'panjang_penggelaran_pipa',
                'bongkaran',
                'tipe_bongkaran',
                'keterangan',
                'foto_evidence_1',
                'foto_evidence_2',
                'foto_evidence_3'
            ];

            $hasChanges = false;
            foreach ($importantFields as $field) {
                if ($jalurLoweringData->wasChanged($field)) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges) {
                Log::info('Dispatching Google Sheets sync job for updated lowering data', [
                    'lowering_id' => $jalurLoweringData->id,
                    'changed_fields' => array_keys($jalurLoweringData->getChanges())
                ]);

                SyncLoweringToGoogleSheets::dispatch($jalurLoweringData);
            }
        }
    }

    /**
     * Handle the JalurLoweringData "deleted" event.
     */
    public function deleted(JalurLoweringData $jalurLoweringData): void
    {
        // Optionally handle deletion - maybe mark as deleted in sheets
        // For now, we'll just log it
        Log::info('Lowering data deleted', [
            'lowering_id' => $jalurLoweringData->id
        ]);
    }

    /**
     * Handle the JalurLoweringData "restored" event.
     */
    public function restored(JalurLoweringData $jalurLoweringData): void
    {
        // Re-sync if restored
        if (config('services.google_sheets.enabled')) {
            SyncLoweringToGoogleSheets::dispatch($jalurLoweringData);
        }
    }

    /**
     * Handle the JalurLoweringData "force deleted" event.
     */
    public function forceDeleted(JalurLoweringData $jalurLoweringData): void
    {
        // Log permanent deletion
        Log::warning('Lowering data permanently deleted', [
            'lowering_id' => $jalurLoweringData->id
        ]);
    }
}
