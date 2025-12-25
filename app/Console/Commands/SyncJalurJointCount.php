<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JalurLineNumber;
use App\Models\JalurJointData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncJalurJointCount extends Command
{
    protected $signature = 'jalur:sync-joint-count
                            {--line-number= : Sync specific line number only}
                            {--cluster= : Sync specific cluster only}
                            {--dry-run : Preview changes without saving}
                            {--force : Skip confirmation}';

    protected $description = 'Sync joint count to jalur_line_numbers table';

    public function handle()
    {
        $specificLineNumber = $this->option('line-number');
        $specificCluster = $this->option('cluster');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Jalur Joint Count Sync");
        $this->newLine();

        // Build query
        $query = JalurLineNumber::query()->with('cluster');

        if ($specificLineNumber) {
            $query->where('line_number', $specificLineNumber);
            $this->info("Filtering: Line Number = {$specificLineNumber}");
        }

        if ($specificCluster) {
            $query->whereHas('cluster', function ($q) use ($specificCluster) {
                $q->where('code_cluster', $specificCluster)
                  ->orWhere('nama_cluster', 'like', "%{$specificCluster}%");
            });
            $this->info("Filtering: Cluster = {$specificCluster}");
        }

        $lineNumbers = $query->get();

        if ($lineNumbers->isEmpty()) {
            $this->warn("No line numbers found with the specified criteria.");
            return 0;
        }

        $this->info("Found {$lineNumbers->count()} line numbers to process");
        $this->newLine();

        if (!$dryRun && !$force) {
            if (!$this->confirm('Do you want to proceed with syncing joint counts?')) {
                $this->info('Sync cancelled.');
                return 0;
            }
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN - No changes will be saved');
        }
        $this->newLine();

        $progressBar = $this->output->createProgressBar($lineNumbers->count());
        $progressBar->start();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'total_joints' => 0,
        ];

        foreach ($lineNumbers as $lineNumber) {
            $counts = $this->calculateJointCounts($lineNumber->line_number);

            $changed = false;
            if ($lineNumber->total_joint_from != $counts['from'] ||
                $lineNumber->total_joint_to != $counts['to'] ||
                $lineNumber->total_joint_optional != $counts['optional'] ||
                $lineNumber->total_joint != $counts['total']) {
                $changed = true;
            }

            if ($changed) {
                $stats['updated']++;

                if (!$dryRun) {
                    $lineNumber->update([
                        'total_joint_from' => $counts['from'],
                        'total_joint_to' => $counts['to'],
                        'total_joint_optional' => $counts['optional'],
                        'total_joint' => $counts['total'],
                    ]);

                    Log::info("Joint count synced", [
                        'line_number' => $lineNumber->line_number,
                        'counts' => $counts
                    ]);
                }
            } else {
                $stats['unchanged']++;
            }

            $stats['processed']++;
            $stats['total_joints'] += $counts['total'];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($stats, $dryRun);

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 This was a dry run. No changes were saved.');
            $this->info('   Remove --dry-run to actually sync the counts.');
        }

        return 0;
    }

    private function calculateJointCounts(string $lineNumber): array
    {
        $countFrom = JalurJointData::where('joint_line_from', $lineNumber)->count();
        $countTo = JalurJointData::where('joint_line_to', $lineNumber)->count();
        $countOptional = JalurJointData::where('joint_line_optional', $lineNumber)
            ->whereNotNull('joint_line_optional')
            ->count();

        // Total is unique count (a joint might use the same line multiple times)
        $total = JalurJointData::where(function ($query) use ($lineNumber) {
            $query->where('joint_line_from', $lineNumber)
                  ->orWhere('joint_line_to', $lineNumber)
                  ->orWhere('joint_line_optional', $lineNumber);
        })->count();

        return [
            'from' => $countFrom,
            'to' => $countTo,
            'optional' => $countOptional,
            'total' => $total,
        ];
    }

    private function displaySummary(array $stats, bool $dryRun): void
    {
        $this->info("=" . str_repeat("=", 60));
        $this->info("Summary");
        $this->info("=" . str_repeat("=", 60));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Line Numbers Processed', $stats['processed']],
                ['Line Numbers Updated', $stats['updated']],
                ['Line Numbers Unchanged', $stats['unchanged']],
                ['Total Joints Counted', $stats['total_joints']],
            ]
        );

        if ($stats['updated'] > 0) {
            $this->newLine();
            if ($dryRun) {
                $this->info("✨ {$stats['updated']} line numbers would be updated");
            } else {
                $this->info("✅ {$stats['updated']} line numbers successfully updated");
            }
        }

        if ($stats['unchanged'] > 0) {
            $this->line("ℹ️  {$stats['unchanged']} line numbers already up to date");
        }
    }
}
