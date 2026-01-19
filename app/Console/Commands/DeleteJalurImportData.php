<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use App\Models\PhotoApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteJalurImportData extends Command
{
    protected $signature = 'jalur:delete-import
                            {--date= : Delete data imported on specific date (Y-m-d format)}
                            {--from-date= : Delete data imported from this date}
                            {--to-date= : Delete data imported until this date}
                            {--module= : Specify module (lowering, joint, or all)}
                            {--all : Delete ALL data without date filter}
                            {--dry-run : Preview what will be deleted without actually deleting}
                            {--force : Skip confirmation}';

    protected $description = 'Delete jalur import data with their associated photos';

    public function handle()
    {
        $date = $this->option('date');
        $fromDate = $this->option('from-date');
        $toDate = $this->option('to-date');
        $module = $this->option('module') ?? 'all';
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $all = $this->option('all');

        if (!$date && !$fromDate && !$toDate && !$all) {
            $this->error("You must specify at least one of: --date, --from-date, --to-date, or --all");
            return 1;
        }

        if ($all && ($date || $fromDate || $toDate)) {
            $this->error("You cannot use --all with date filters.");
            return 1;
        }

        $this->info("Jalur Import Data Deletion");
        $this->info("Module: " . ($module === 'all' ? 'Lowering & Joint' : ucfirst($module)));

        if ($all) {
            $this->warn("⚠️  DELETING ALL DATA (No Date Filter) ⚠️");
        }
        $this->newLine();

        $stats = [];

        // Process lowering data
        if (in_array($module, ['lowering', 'all'])) {
            $stats['lowering'] = $this->processLowering($date, $fromDate, $toDate, $dryRun);
        }

        // Process joint data
        if (in_array($module, ['joint', 'all'])) {
            $stats['joint'] = $this->processJoint($date, $fromDate, $toDate, $dryRun);
        }

        // Display summary
        $this->newLine();
        $this->displaySummary($stats, $dryRun);

        // Confirm deletion if not dry-run
        if (!$dryRun && !$force) {
            $this->newLine();
            if (!$this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Deletion cancelled.');
                return 0;
            }

            // Actually delete
            $this->info('Deleting data...');
            $this->performDeletion($stats);
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 This was a dry run. No data was deleted.');
            $this->info('   Remove --dry-run to actually delete the data.');
        } else if ($force) {
            $this->performDeletion($stats);
        }

        return 0;
    }

    private function processLowering(?string $date, ?string $fromDate, ?string $toDate, bool $dryRun): array
    {
        $this->info("📋 Checking Lowering Data...");

        $query = JalurLoweringData::query();

        if ($date) {
            $query->whereDate('created_at', $date);
        } else {
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        }

        $loweringData = $query->with('photoApprovals')->get();

        $this->line("Found " . $loweringData->count() . " lowering records");

        $photoCount = 0;
        $approvedCount = 0;
        $recordIds = [];

        foreach ($loweringData as $lowering) {
            $recordIds[] = $lowering->id;
            $photos = $lowering->photoApprovals;
            $photoCount += $photos->count();

            foreach ($photos as $photo) {
                if ($photo->photo_status !== 'draft' && $photo->photo_status !== 'tracer_pending') {
                    $approvedCount++;
                }
            }
        }

        $this->line("  - Total photos: {$photoCount}");
        if ($approvedCount > 0) {
            $this->warn("  - ⚠️  Photos with approval: {$approvedCount}");
        }

        return [
            'count' => $loweringData->count(),
            'photo_count' => $photoCount,
            'approved_count' => $approvedCount,
            'ids' => $recordIds
        ];
    }

    private function processJoint(?string $date, ?string $fromDate, ?string $toDate, bool $dryRun): array
    {
        $this->info("📋 Checking Joint Data...");

        $query = JalurJointData::query();

        if ($date) {
            $query->whereDate('created_at', $date);
        } else {
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        }

        $jointData = $query->with('photoApprovals')->get();

        $this->line("Found " . $jointData->count() . " joint records");

        $photoCount = 0;
        $approvedCount = 0;
        $recordIds = [];

        foreach ($jointData as $joint) {
            $recordIds[] = $joint->id;
            $photos = $joint->photoApprovals;
            $photoCount += $photos->count();

            foreach ($photos as $photo) {
                if ($photo->photo_status !== 'draft' && $photo->photo_status !== 'tracer_pending') {
                    $approvedCount++;
                }
            }
        }

        $this->line("  - Total photos: {$photoCount}");
        if ($approvedCount > 0) {
            $this->warn("  - ⚠️  Photos with approval: {$approvedCount}");
        }

        return [
            'count' => $jointData->count(),
            'photo_count' => $photoCount,
            'approved_count' => $approvedCount,
            'ids' => $recordIds
        ];
    }

    private function displaySummary(array $stats, bool $dryRun): void
    {
        $this->info("=" . str_repeat("=", 60));
        $this->info("Summary");
        $this->info("=" . str_repeat("=", 60));

        if (isset($stats['lowering'])) {
            $this->line("Lowering:");
            $this->line("  - Records: {$stats['lowering']['count']}");
            $this->line("  - Photos: {$stats['lowering']['photo_count']}");
            if ($stats['lowering']['approved_count'] > 0) {
                $this->warn("  - Approved photos: {$stats['lowering']['approved_count']}");
            }
        }

        if (isset($stats['joint'])) {
            $this->line("Joint:");
            $this->line("  - Records: {$stats['joint']['count']}");
            $this->line("  - Photos: {$stats['joint']['photo_count']}");
            if ($stats['joint']['approved_count'] > 0) {
                $this->warn("  - Approved photos: {$stats['joint']['approved_count']}");
            }
        }

        $totalRecords = ($stats['lowering']['count'] ?? 0) + ($stats['joint']['count'] ?? 0);
        $totalPhotos = ($stats['lowering']['photo_count'] ?? 0) + ($stats['joint']['photo_count'] ?? 0);
        $totalApproved = ($stats['lowering']['approved_count'] ?? 0) + ($stats['joint']['approved_count'] ?? 0);

        $this->newLine();
        $this->info("Total Records to delete: {$totalRecords}");
        $this->info("Total Photos to delete: {$totalPhotos}");
        if ($totalApproved > 0) {
            $this->warn("⚠️  Total Approved photos that will be lost: {$totalApproved}");
        }
    }

    private function performDeletion(array $stats): void
    {
        DB::beginTransaction();

        try {
            $deletedRecords = 0;
            $deletedPhotos = 0;

            // Delete lowering data
            if (isset($stats['lowering']) && !empty($stats['lowering']['ids'])) {
                // Delete photos first
                $photoCount = PhotoApproval::where('module_name', 'jalur_lowering')
                    ->whereIn('module_record_id', $stats['lowering']['ids'])
                    ->delete();
                $deletedPhotos += $photoCount;

                // Delete lowering records
                $recordCount = JalurLoweringData::whereIn('id', $stats['lowering']['ids'])->delete();
                $deletedRecords += $recordCount;

                $this->info("✅ Deleted {$recordCount} lowering records and {$photoCount} photos");
            }

            // Delete joint data
            if (isset($stats['joint']) && !empty($stats['joint']['ids'])) {
                // Delete photos first
                $photoCount = PhotoApproval::where('module_name', 'jalur_joint')
                    ->whereIn('module_record_id', $stats['joint']['ids'])
                    ->delete();
                $deletedPhotos += $photoCount;

                // Delete joint records
                $recordCount = JalurJointData::whereIn('id', $stats['joint']['ids'])->delete();
                $deletedRecords += $recordCount;

                $this->info("✅ Deleted {$recordCount} joint records and {$photoCount} photos");
            }

            DB::commit();

            $this->newLine();
            $this->info("🎉 Deletion completed successfully!");
            $this->info("   Total records deleted: {$deletedRecords}");
            $this->info("   Total photos deleted: {$deletedPhotos}");

            Log::info("Jalur import data deleted", [
                'records_deleted' => $deletedRecords,
                'photos_deleted' => $deletedPhotos,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("❌ Deletion failed: " . $e->getMessage());
            Log::error("Failed to delete jalur import data", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return;
        }
    }
}
