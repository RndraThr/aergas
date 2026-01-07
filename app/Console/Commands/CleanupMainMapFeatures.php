<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MapGeometricFeature;
use Illuminate\Support\Facades\Log;

class CleanupMainMapFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:cleanup-main-map-features 
                            {--dry-run : Run without actually deleting}
                            {--action=hide : Action to perform (hide or delete)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup jalur features from main map (features with line_number_id or cluster_id)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $action = $this->option('action');

        $this->info('🧹 Cleanup Main Map Features Tool');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Action: ' . strtoupper($action));
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find jalur features (has line_number_id or cluster_id)
        $jalurFeatures = MapGeometricFeature::where(function ($q) {
            $q->whereNotNull('line_number_id')
                ->orWhereNotNull('cluster_id');
        })->get();

        if ($jalurFeatures->isEmpty()) {
            $this->info('✅ No jalur features found. Main map is clean!');
            return 0;
        }

        $this->info("Found {$jalurFeatures->count()} jalur features:");
        $this->newLine();

        // Group by type
        $assigned = $jalurFeatures->whereNotNull('line_number_id');
        $clusterOnly = $jalurFeatures->whereNull('line_number_id')->whereNotNull('cluster_id');

        $this->table(
            ['Type', 'Count', 'Details'],
            [
                [
                    'Assigned to Line Number',
                    $assigned->count(),
                    $assigned->count() > 0 ? 'Will be ' . ($action === 'delete' ? 'DELETED' : 'HIDDEN') : '-'
                ],
                [
                    'Cluster Boundaries',
                    $clusterOnly->count(),
                    $clusterOnly->count() > 0 ? 'Will be ' . ($action === 'delete' ? 'DELETED' : 'HIDDEN') : '-'
                ],
                [
                    'TOTAL',
                    $jalurFeatures->count(),
                    ''
                ]
            ]
        );

        $this->newLine();

        if (!$dryRun) {
            if (!$this->confirm('Proceed with ' . strtoupper($action) . '?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $processed = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($jalurFeatures->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($jalurFeatures as $feature) {
            $progressBar->setMessage("Processing feature #{$feature->id}...");
            $progressBar->advance();

            try {
                if (!$dryRun) {
                    if ($action === 'delete') {
                        $feature->delete();
                        Log::info("Deleted jalur feature from main map: {$feature->id} - {$feature->name}");
                    } else {
                        $feature->update(['is_visible' => false]);
                        Log::info("Hidden jalur feature from main map: {$feature->id} - {$feature->name}");
                    }
                }
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("Failed to process feature {$feature->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 Summary:');
        $this->info("   Total Processed: {$processed}");
        if ($action === 'delete') {
            $this->info("   ✅ Deleted: {$processed}");
        } else {
            $this->info("   ✅ Hidden: {$processed}");
        }
        $this->info("   ❌ Errors: {$errors}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. No actual changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Cleanup completed!');
            $this->info('Main map dashboard will no longer show jalur features.');
            $this->info('Jalur features are still available in Jalur Dashboard.');
        }

        return 0;
    }
}
