<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;

class RenameJalurPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:rename-photos 
                            {--module=all : Module to process (lowering, joint, or all)}
                            {--dry-run : Run without actually renaming files}
                            {--limit=0 : Limit number of files to process (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename old jalur photo files to new descriptive format';

    private GoogleDriveService $googleDriveService;
    private int $processedCount = 0;
    private int $renamedCount = 0;
    private int $skippedCount = 0;
    private int $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->googleDriveService = app(GoogleDriveService::class);
        $module = $this->option('module');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info("🔧 Jalur Photo Renaming Tool");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Module: " . strtoupper($module));
        $this->info("Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE (will rename files)"));
        if ($limit > 0) {
            $this->info("Limit: {$limit} files");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No files will be renamed");
            $this->newLine();
        }

        if (!$dryRun && !$this->confirm('This will rename files in Google Drive. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process based on module selection
        if ($module === 'all' || $module === 'lowering') {
            $this->processModule('jalur_lowering', $dryRun, $limit);
        }

        if ($module === 'all' || $module === 'joint') {
            $this->processModule('jalur_joint', $dryRun, $limit);
        }

        // Summary
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Summary:");
        $this->info("   Total Processed: {$this->processedCount}");
        $this->info("   ✅ Renamed: {$this->renamedCount}");
        $this->info("   ⏭️  Skipped: {$this->skippedCount}");
        $this->info("   ❌ Errors: {$this->errorCount}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return 0;
    }

    private function processModule(string $moduleName, bool $dryRun, int $limit): void
    {
        $this->info("📁 Processing {$moduleName}...");
        $this->newLine();

        // Get photos with old naming convention
        $query = PhotoApproval::where('module_name', $moduleName)
            ->whereNotNull('drive_file_id')
            ->where(function ($q) {
                // Old naming patterns
                $q->where('storage_path', 'LIKE', '%foto_evidence_%')
                    ->orWhere('storage_path', 'LIKE', '%_' . '%'); // Contains timestamp pattern
            });

        if ($limit > 0) {
            $remainingLimit = $limit - $this->processedCount;
            if ($remainingLimit <= 0) {
                return;
            }
            $query->limit($remainingLimit);
        }

        $photos = $query->get();

        if ($photos->isEmpty()) {
            $this->info("   No photos to rename for {$moduleName}");
            return;
        }

        $progressBar = $this->output->createProgressBar($photos->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($photos as $photo) {
            $this->processedCount++;

            $progressBar->setMessage("Processing photo ID {$photo->id}...");
            $progressBar->advance();

            try {
                // Check if already has new format
                if ($this->hasNewFormat($photo, $moduleName)) {
                    $this->skippedCount++;
                    continue;
                }

                // Generate new filename
                $newFilename = $this->generateNewFilename($photo, $moduleName);

                if (!$newFilename) {
                    $this->skippedCount++;
                    continue;
                }

                // Rename file in Google Drive
                if (!$dryRun) {
                    $this->renameFileInDrive($photo, $newFilename);
                }

                $this->renamedCount++;

            } catch (\Exception $e) {
                $this->errorCount++;
                Log::error("Failed to rename photo {$photo->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function hasNewFormat(PhotoApproval $photo, string $moduleName): bool
    {
        $storagePath = $photo->storage_path;

        if (!$storagePath) {
            return false;
        }

        // Check if filename starts with LOWERING_ or JOINT_
        $filename = basename($storagePath);

        if ($moduleName === 'jalur_lowering') {
            return str_starts_with($filename, 'LOWERING_');
        } elseif ($moduleName === 'jalur_joint') {
            return str_starts_with($filename, 'JOINT_');
        }

        return false;
    }

    private function generateNewFilename(PhotoApproval $photo, string $moduleName): ?string
    {
        try {
            if ($moduleName === 'jalur_lowering') {
                return $this->generateLoweringFilename($photo);
            } elseif ($moduleName === 'jalur_joint') {
                return $this->generateJointFilename($photo);
            }
        } catch (\Exception $e) {
            Log::warning("Could not generate filename for photo {$photo->id}: " . $e->getMessage());
        }

        return null;
    }

    private function generateLoweringFilename(PhotoApproval $photo): ?string
    {
        $lowering = \App\Models\JalurLoweringData::find($photo->module_record_id);

        if (!$lowering || !$lowering->lineNumber) {
            return null;
        }

        $lineNumber = $lowering->lineNumber->line_number;
        $tanggal = $lowering->tanggal_jalur->format('Y-m-d');
        $waktu = $lowering->created_at->format('H-i-s');
        $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $photo->photo_field_name);

        // Get file extension from original filename
        $oldFilename = basename($photo->storage_path);
        $ext = pathinfo($oldFilename, PATHINFO_EXTENSION) ?: 'jpg';

        return "LOWERING_{$lineNumber}_{$tanggal}_{$waktu}_{$fieldSlug}.{$ext}";
    }

    private function generateJointFilename(PhotoApproval $photo): ?string
    {
        $joint = \App\Models\JalurJointData::find($photo->module_record_id);

        if (!$joint) {
            return null;
        }

        $nomorJoint = $joint->nomor_joint;
        $tanggal = $joint->tanggal_joint->format('Y-m-d');
        $waktu = $joint->created_at->format('H-i-s');
        $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $photo->photo_field_name);

        // Get file extension from original filename
        $oldFilename = basename($photo->storage_path);
        $ext = pathinfo($oldFilename, PATHINFO_EXTENSION) ?: 'jpg';

        return "JOINT_{$nomorJoint}_{$tanggal}_{$waktu}_{$fieldSlug}.{$ext}";
    }

    private function renameFileInDrive(PhotoApproval $photo, string $newFilename): void
    {
        try {
            // Rename file in Google Drive
            $result = $this->googleDriveService->renameFile($photo->drive_file_id, $newFilename);

            if ($result) {
                // Update storage_path in database
                $oldPath = $photo->storage_path;
                $newPath = dirname($oldPath) . '/' . $newFilename;

                $photo->update([
                    'storage_path' => $newPath,
                    'stored_filename' => $newFilename
                ]);

                Log::info("Renamed photo {$photo->id}: {$oldPath} → {$newPath}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to rename file in Drive for photo {$photo->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
