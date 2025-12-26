<?php

namespace App\Console\Commands;

use App\Models\JalurJointData;
use App\Models\JalurJointNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncJalurJointNumbers extends Command
{
    protected $signature = 'jalur:sync-joint-numbers
                            {--dry-run : Preview changes without saving}
                            {--force : Skip confirmation}';

    protected $description = 'Sync jalur_joint_numbers table from existing jalur_joint_data';

    public function handle()
    {
        $this->info('🔄 Syncing Joint Numbers from Joint Data...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN - No changes will be saved');
            $this->newLine();
        }

        // Get all unique joint numbers from jalur_joint_data
        $jointData = JalurJointData::select('nomor_joint', 'cluster_id', 'fitting_type_id', 'joint_code')
            ->distinct()
            ->orderBy('nomor_joint')
            ->get();

        $this->info("Found {$jointData->count()} unique joint numbers in jalur_joint_data");
        $this->newLine();

        if (!$force && !$dryRun) {
            if (!$this->confirm('Do you want to continue?')) {
                $this->warn('Cancelled.');
                return 0;
            }
        }

        $created = 0;
        $existing = 0;
        $errors = 0;

        $this->withProgressBar($jointData, function ($joint) use (&$created, &$existing, &$errors, $dryRun) {
            try {
                // Check if joint number already exists
                $existingJoint = JalurJointNumber::where('nomor_joint', $joint->nomor_joint)->first();

                if ($existingJoint) {
                    $existing++;
                    return;
                }

                if (!$dryRun) {
                    // Create new joint number record
                    JalurJointNumber::create([
                        'nomor_joint' => $joint->nomor_joint,
                        'cluster_id' => $joint->cluster_id,
                        'fitting_type_id' => $joint->fitting_type_id,
                        'joint_code' => $joint->joint_code,
                        'is_used' => true, // Mark as used since it's from actual joint data
                        'is_active' => true,
                        'created_by' => 1, // System user
                        'updated_by' => 1,
                    ]);
                }

                $created++;

            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing {$joint->nomor_joint}: " . $e->getMessage());
            }
        });

        $this->newLine(2);

        // Summary
        $this->line('=============================================================');
        $this->info('Summary');
        $this->line('=============================================================');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Joint Numbers Processed', $jointData->count()],
                ['New Records Created', $created],
                ['Already Existing', $existing],
                ['Errors', $errors],
            ]
        );

        if ($created > 0) {
            $this->info("✅ {$created} new joint number records created");
        }

        if ($existing > 0) {
            $this->line("ℹ️  {$existing} joint numbers already exist in jalur_joint_numbers");
        }

        if ($errors > 0) {
            $this->error("❌ {$errors} errors occurred");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('💡 This was a dry run. No changes were saved.');
            $this->line('   Remove --dry-run to actually create the records.');
        }

        return 0;
    }
}
