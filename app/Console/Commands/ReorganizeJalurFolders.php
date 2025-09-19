<?php

namespace App\Console\Commands;

use App\Jobs\ReorganizeJalurLoweringFolder;
use App\Models\JalurLoweringData;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReorganizeJalurFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jalur:reorganize-folders
                            {--lowering-id= : Specific lowering data ID to reorganize}
                            {--check-all : Check all lowering data for mismatched folders}
                            {--dry-run : Show what would be reorganized without making changes}
                            {--force : Force reorganization even if no mismatch detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and reorganize jalur lowering folders based on tanggal_jalur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $loweringId = $this->option('lowering-id');
        $checkAll = $this->option('check-all');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        try {
            $googleDriveService = app(GoogleDriveService::class);

            if ($loweringId) {
                $this->reorganizeSpecificLowering($loweringId, $googleDriveService, $dryRun, $force);
            } elseif ($checkAll) {
                $this->checkAllLoweringData($googleDriveService, $dryRun);
            } else {
                $this->error('Please specify either --lowering-id=ID or --check-all option');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Reorganize folders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    private function reorganizeSpecificLowering(int $loweringId, GoogleDriveService $service, bool $dryRun, bool $force)
    {
        $lowering = JalurLoweringData::with(['lineNumber.cluster'])->find($loweringId);

        if (!$lowering) {
            $this->error("Lowering data with ID {$loweringId} not found");
            return;
        }

        $this->info("Processing lowering ID: {$loweringId}");
        $this->info("Line: {$lowering->lineNumber->line_number}");
        $this->info("Cluster: {$lowering->lineNumber->cluster->nama_cluster}");
        $this->info("Date: {$lowering->tanggal_jalur}");

        // Check if there are photos to reorganize
        $photos = PhotoApproval::where('module_name', 'jalur_lowering')
            ->where('module_record_id', $loweringId)
            ->get();

        if ($photos->isEmpty()) {
            $this->warn('No photos found for this lowering data');
            return;
        }

        $this->info("Found {$photos->count()} photos to check");

        // Detect current folder structure vs expected
        $mismatches = $this->detectFolderMismatches($photos, $lowering, $service);

        if (!$force && empty($mismatches)) {
            $this->info('No folder mismatches detected');
            return;
        }

        if (!empty($mismatches)) {
            $this->info('Folder mismatches detected:');
            foreach ($mismatches as $mismatch) {
                $this->line("  Expected: {$mismatch['expected']}");
                $this->line("  Current:  {$mismatch['current']}");
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN: Would reorganize folders for this lowering data');
            return;
        }

        // Perform reorganization using old date detection
        $this->performReorganization($lowering, $service);
    }

    private function checkAllLoweringData(GoogleDriveService $service, bool $dryRun)
    {
        $this->info('Checking all lowering data for folder mismatches...');

        $loweringData = JalurLoweringData::with(['lineNumber.cluster'])
            ->whereHas('photoApprovals')
            ->get();

        $this->info("Found {$loweringData->count()} lowering records with photos");

        $mismatched = [];

        foreach ($loweringData as $lowering) {
            $photos = $lowering->photoApprovals;

            $mismatches = $this->detectFolderMismatches($photos, $lowering, $service);

            if (!empty($mismatches)) {
                $mismatched[] = [
                    'lowering' => $lowering,
                    'mismatches' => $mismatches
                ];
            }
        }

        if (empty($mismatched)) {
            $this->info('✅ All folders are properly organized');
            return;
        }

        $this->warn("Found " . count($mismatched) . " lowering records with folder mismatches:");

        foreach ($mismatched as $item) {
            $lowering = $item['lowering'];
            $this->newLine();
            $this->info("Lowering ID: {$lowering->id}");
            $this->info("Line: {$lowering->lineNumber->line_number}");
            $this->info("Date: {$lowering->tanggal_jalur}");

            foreach ($item['mismatches'] as $mismatch) {
                $this->line("  Expected: {$mismatch['expected']}");
                $this->line("  Current:  {$mismatch['current']}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN: Run without --dry-run to reorganize all mismatched folders');
        } else {
            $this->newLine();
            if ($this->confirm('Do you want to reorganize all mismatched folders?')) {
                foreach ($mismatched as $item) {
                    $lowering = $item['lowering'];
                    $this->info("Reorganizing lowering ID: {$lowering->id}");
                    $this->performReorganization($lowering, $service);
                }
                $this->info('✅ All reorganizations completed');
            }
        }
    }

    private function detectFolderMismatches($photos, JalurLoweringData $lowering, GoogleDriveService $service): array
    {
        $mismatches = [];
        $expectedPath = $this->getExpectedFolderPath($lowering);

        foreach ($photos as $photo) {
            if ($photo->google_drive_url) {
                $currentPath = $this->extractFolderPathFromUrl($photo->google_drive_url, $service);

                if ($currentPath && $currentPath !== $expectedPath) {
                    $mismatches[] = [
                        'photo_id' => $photo->id,
                        'expected' => $expectedPath,
                        'current' => $currentPath,
                        'url' => $photo->google_drive_url
                    ];
                }
            }
        }

        return array_unique($mismatches, SORT_REGULAR);
    }

    private function getExpectedFolderPath(JalurLoweringData $lowering): string
    {
        $clusterName = str_replace(' ', '_', $lowering->lineNumber->cluster->nama_cluster);
        $lineNumber = $lowering->lineNumber->line_number;
        $date = $lowering->tanggal_jalur instanceof \Carbon\Carbon
            ? $lowering->tanggal_jalur->format('Y-m-d')
            : $lowering->tanggal_jalur;

        return "JALUR_LOWERING/{$clusterName}/{$lineNumber}/{$date}";
    }

    private function extractFolderPathFromUrl(string $url, GoogleDriveService $service): ?string
    {
        try {
            // Extract file ID from Google Drive URL
            if (preg_match('/\/file\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
                $fileId = $matches[1];

                // Use reflection to access private drive property
                $reflection = new \ReflectionClass($service);
                $driveProperty = $reflection->getProperty('drive');
                $driveProperty->setAccessible(true);
                $drive = $driveProperty->getValue($service);

                if (!$drive) {
                    // Initialize service if not already done
                    $initMethod = $reflection->getMethod('initialize');
                    $initMethod->setAccessible(true);
                    if (!$initMethod->invoke($service)) {
                        return null;
                    }
                    $drive = $driveProperty->getValue($service);
                }

                // Get file metadata from Google Drive
                $file = $drive->files->get($fileId, [
                    'fields' => 'parents'
                ]);

                if (!empty($file->parents)) {
                    $parentId = $file->parents[0];
                    return $this->buildFolderPath($parentId, $drive);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract folder path from URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    private function buildFolderPath(string $folderId, Drive $drive): ?string
    {
        try {
            $path = [];
            $currentId = $folderId;

            // Build path by walking up the hierarchy
            while ($currentId && $currentId !== 'root') {
                $folder = $drive->files->get($currentId, [
                    'fields' => 'name,parents'
                ]);

                array_unshift($path, $folder->name);

                if (!empty($folder->parents)) {
                    $currentId = $folder->parents[0];
                } else {
                    break;
                }

                // Prevent infinite loops
                if (count($path) > 20) {
                    break;
                }
            }

            return implode('/', $path);
        } catch (\Exception $e) {
            Log::warning('Failed to build folder path', [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function performReorganization(JalurLoweringData $lowering, GoogleDriveService $service)
    {
        // Estimate what the old date might have been by checking current folder structure
        $photos = $lowering->photoApprovals;
        $oldDate = null;

        if ($photos->isNotEmpty()) {
            $firstPhoto = $photos->first();
            if ($firstPhoto->google_drive_url) {
                $currentPath = $this->extractFolderPathFromUrl($firstPhoto->google_drive_url, $service);
                if ($currentPath && preg_match('/(\d{4}-\d{2}-\d{2})$/', $currentPath, $matches)) {
                    $oldDate = $matches[1];
                }
            }
        }

        if (!$oldDate) {
            $this->warn("Cannot determine old date for lowering ID: {$lowering->id}");
            return;
        }

        $newDate = $lowering->tanggal_jalur instanceof \Carbon\Carbon
            ? $lowering->tanggal_jalur->format('Y-m-d')
            : $lowering->tanggal_jalur;

        if ($oldDate === $newDate) {
            $this->info("Dates match for lowering ID: {$lowering->id}, no reorganization needed");
            return;
        }

        // Queue reorganization job
        ReorganizeJalurLoweringFolder::dispatch($lowering->id, $oldDate, $newDate);

        $this->info("✅ Reorganization job queued for lowering ID: {$lowering->id}");
        $this->line("   From: {$oldDate} → To: {$newDate}");
    }
}
