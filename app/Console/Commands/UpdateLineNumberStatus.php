<?php

namespace App\Console\Commands;

use App\Models\JalurLineNumber;
use Illuminate\Console\Command;

class UpdateLineNumberStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:update-status {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all line number statuses based on current penggelaran data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $lineNumbers = JalurLineNumber::with('loweringData')->get();
        $updated = 0;
        $statusChanges = [];

        foreach ($lineNumbers as $lineNumber) {
            $oldStatus = $lineNumber->status_line;

            // Update totals first
            if (!$dryRun) {
                $lineNumber->updateTotalPenggelaran();
                $lineNumber->refresh(); // Reload to get updated total_penggelaran
            }

            // Calculate what the new status should be
            $newStatus = 'draft';

            if ($lineNumber->loweringData()->count() > 0) {
                $newStatus = 'in_progress';
            }

            if ($lineNumber->actual_mc100 !== null) {
                $newStatus = 'completed';
            } elseif ($lineNumber->estimasi_panjang > 0 && $lineNumber->total_penggelaran >= $lineNumber->estimasi_panjang) {
                $newStatus = 'completed';
            }

            if ($oldStatus !== $newStatus) {
                $statusChanges[] = [
                    'line_number' => $lineNumber->line_number,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'total_penggelaran' => $lineNumber->total_penggelaran,
                    'estimasi_panjang' => $lineNumber->estimasi_panjang
                ];

                if (!$dryRun) {
                    $lineNumber->update(['status_line' => $newStatus]);
                }
                $updated++;
            }
        }

        // Display results
        if (empty($statusChanges)) {
            $this->info('No status changes needed.');
        } else {
            $this->info("Found {$updated} line numbers that need status updates:");
            $this->table(
                ['Line Number', 'Old Status', 'New Status', 'Penggelaran', 'Estimasi'],
                array_map(function($change) {
                    return [
                        $change['line_number'],
                        $change['old_status'],
                        $change['new_status'],
                        $change['total_penggelaran'] . 'm',
                        $change['estimasi_panjang'] . 'm'
                    ];
                }, $statusChanges)
            );

            if ($dryRun) {
                $this->warn('DRY RUN: No changes were made. Run without --dry-run to apply changes.');
            } else {
                $this->info("Successfully updated {$updated} line number statuses.");
            }
        }

        return 0;
    }
}
