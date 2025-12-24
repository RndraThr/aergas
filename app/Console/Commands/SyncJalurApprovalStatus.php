<?php

namespace App\Console\Commands;

use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncJalurApprovalStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:sync-approval-status
                            {--module= : Specify module (lowering, joint, or all)}
                            {--dry-run : Preview changes without saving}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync jalur approval status from photo_approvals table (fix historical data)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $module = $this->option('module') ?: 'all';
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔄 Jalur Approval Status Sync Tool');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        // Confirmation
        if (!$force && !$dryRun) {
            if (!$this->confirm('This will update status_laporan for all jalur records based on photo approvals. Continue?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        $totalUpdated = 0;

        // Process Lowering Data
        if (in_array($module, ['lowering', 'all'])) {
            $this->info('📊 Processing Jalur Lowering Data...');
            $totalUpdated += $this->syncLoweringData($dryRun);
            $this->newLine();
        }

        // Process Joint Data
        if (in_array($module, ['joint', 'all'])) {
            $this->info('🔗 Processing Jalur Joint Data...');
            $totalUpdated += $this->syncJointData($dryRun);
            $this->newLine();
        }

        if ($dryRun) {
            $this->info("✅ Dry run completed. {$totalUpdated} records would be updated.");
        } else {
            $this->info("✅ Sync completed successfully! {$totalUpdated} records updated.");
        }

        return 0;
    }

    /**
     * Sync lowering data approval statuses
     */
    private function syncLoweringData(bool $dryRun = false): int
    {
        $loweringRecords = JalurLoweringData::with('photoApprovals')->get();
        $updated = 0;
        $stats = [
            'draft' => 0,
            'acc_tracer' => 0,
            'acc_cgp' => 0,
            'revisi_tracer' => 0,
            'revisi_cgp' => 0,
        ];

        $this->withProgressBar($loweringRecords, function ($lowering) use (&$updated, &$stats, $dryRun) {
            $oldStatus = $lowering->status_laporan;

            // Call the sync method (with save=false for dry run)
            $newStatus = $lowering->syncModuleStatusFromPhotos(!$dryRun);

            if ($oldStatus !== $newStatus) {
                $updated++;
                $stats[$newStatus] = ($stats[$newStatus] ?? 0) + 1;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("  Lowering ID {$lowering->id}: {$oldStatus} → {$newStatus}");
                }
            }
        });

        $this->newLine();
        $this->line("  Updated: {$updated} lowering records");

        if ($updated > 0) {
            $this->table(
                ['Status', 'Count'],
                collect($stats)->filter()->map(fn($count, $status) => [$status, $count])->toArray()
            );
        }

        return $updated;
    }

    /**
     * Sync joint data approval statuses
     */
    private function syncJointData(bool $dryRun = false): int
    {
        $jointRecords = JalurJointData::with('photoApprovals')->get();
        $updated = 0;
        $stats = [
            'draft' => 0,
            'acc_tracer' => 0,
            'acc_cgp' => 0,
            'revisi_tracer' => 0,
            'revisi_cgp' => 0,
        ];

        $this->withProgressBar($jointRecords, function ($joint) use (&$updated, &$stats, $dryRun) {
            $oldStatus = $joint->status_laporan;

            // Call the sync method (with save=false for dry run)
            $newStatus = $joint->syncModuleStatusFromPhotos(!$dryRun);

            if ($oldStatus !== $newStatus) {
                $updated++;
                $stats[$newStatus] = ($stats[$newStatus] ?? 0) + 1;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("  Joint ID {$joint->id}: {$oldStatus} → {$newStatus}");
                }
            }
        });

        $this->newLine();
        $this->line("  Updated: {$updated} joint records");

        if ($updated > 0) {
            $this->table(
                ['Status', 'Count'],
                collect($stats)->filter()->map(fn($count, $status) => [$status, $count])->toArray()
            );
        }

        return $updated;
    }
}
