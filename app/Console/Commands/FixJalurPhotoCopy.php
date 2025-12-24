<?php

namespace App\Console\Commands;

use App\Models\PhotoApproval;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FixJalurPhotoCopy extends Command
{
    protected $signature = 'jalur:fix-photo-copy
                            {--dry-run : Preview without copying}
                            {--force : Skip confirmation}
                            {--limit= : Limit number of photos to process}';

    protected $description = 'Fix jalur photos that have photo_url but not copied to our Google Drive';

    private GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        parent::__construct();
        $this->googleDriveService = $googleDriveService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $limit = $this->option('limit');

        $this->info('🔧 Jalur Photo Copy Fix Tool');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No photos will be copied');
            $this->newLine();
        }

        // Find photos that need to be copied (has photo_url but empty drive_file_id)
        $query = PhotoApproval::whereIn('module_name', ['jalur_lowering', 'jalur_joint'])
            ->whereNotNull('photo_url')
            ->where(function($q) {
                $q->whereNull('drive_file_id')
                  ->orWhere('drive_file_id', '');
            })
            ->where('photo_url', 'like', '%drive.google.com%');

        if ($limit) {
            $query->limit((int)$limit);
        }

        $photos = $query->get();

        if ($photos->isEmpty()) {
            $this->info('✅ No photos need fixing!');
            return 0;
        }

        $this->info("Found {$photos->count()} photos that need to be copied");
        $this->newLine();

        if (!$force && !$dryRun) {
            if (!$this->confirm('Continue to copy these photos to our Google Drive?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $this->withProgressBar($photos, function ($photo) use (&$success, &$failed, &$skipped, $dryRun) {
            try {
                if ($dryRun) {
                    $this->info("\n  Would copy: {$photo->photo_field_name} (ID: {$photo->id})");
                    $success++;
                    return;
                }

                // Get module data for folder structure
                if ($photo->module_name === 'jalur_lowering') {
                    $moduleData = JalurLoweringData::with('lineNumber.cluster')->find($photo->module_record_id);
                    if (!$moduleData) {
                        $skipped++;
                        return;
                    }
                    $clusterSlug = \Illuminate\Support\Str::slug($moduleData->lineNumber->cluster->nama_cluster, '_');
                    $lineNumber = $moduleData->lineNumber->line_number;
                    $date = $moduleData->tanggal_jalur->format('Y-m-d');
                    $customPath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$date}";
                } else {
                    $moduleData = JalurJointData::with('cluster')->find($photo->module_record_id);
                    if (!$moduleData) {
                        $skipped++;
                        return;
                    }
                    $clusterSlug = \Illuminate\Support\Str::slug($moduleData->cluster->nama_cluster, '_');
                    $jointNumber = $moduleData->nomor_joint;
                    $date = $moduleData->tanggal_joint->format('Y-m-d');
                    $customPath = "jalur_joint/{$clusterSlug}/{$jointNumber}/{$date}";
                }

                // Copy from Google Drive link
                $result = $this->googleDriveService->copyFromDriveLink(
                    $photo->photo_url,
                    $customPath,
                    $photo->photo_field_name . '_' . time()
                );

                // Update photo record
                $photo->update([
                    'drive_file_id' => $result['drive_file_id'] ?? null,
                    'drive_link' => $result['url'] ?? null,
                    'storage_disk' => 'google_drive',
                    'storage_path' => $customPath,
                ]);

                $success++;

            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to copy photo', [
                    'photo_id' => $photo->id,
                    'error' => $e->getMessage()
                ]);

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->error("  Failed: {$photo->id} - {$e->getMessage()}");
                }
            }
        });

        $this->newLine(2);
        $this->table(
            ['Result', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Skipped', $skipped],
                ['Total', $photos->count()],
            ]
        );

        if ($dryRun) {
            $this->info("✅ Dry run completed. {$success} photos would be copied.");
        } else {
            $this->info("✅ Fix completed! {$success} photos copied successfully.");
            if ($failed > 0) {
                $this->warn("⚠️  {$failed} photos failed. Check logs for details.");
            }
        }

        return 0;
    }
}
