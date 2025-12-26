<?php

namespace App\Console\Commands;

use App\Models\JalurJointData;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class CheckJalurJointDriveFolder extends Command
{
    protected $signature = 'jalur:check-joint-drive-folder
                            {--limit=10 : Number of photos to check}
                            {--full : Show full folder tree for all joints}';

    protected $description = 'Check jalur joint photos in Google Drive and verify folder structure';

    private GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        parent::__construct();
        $this->driveService = $driveService;
    }

    public function handle()
    {
        $this->info('🔍 Checking Jalur Joint folders in Google Drive...');
        $this->newLine();

        // Check if Drive is available
        if (!$this->driveService->isAvailable()) {
            $this->error('❌ Google Drive service is not available!');
            $this->error('Error: ' . $this->driveService->getError());
            return 1;
        }

        $this->info('✅ Google Drive connected successfully');
        $this->newLine();

        // Get statistics
        $totalJoints = JalurJointData::count();
        $totalPhotos = PhotoApproval::where('module_name', 'jalur_joint')->count();

        $this->info("📊 Database Statistics:");
        $this->line("   Total Joint Records: {$totalJoints}");
        $this->line("   Total Joint Photos: {$totalPhotos}");
        $this->newLine();

        // Check jalur_joint folder structure
        $this->info('📁 Checking folder structure in Google Drive...');
        $this->checkFolderStructure();
        $this->newLine();

        // Check sample photos
        $limit = $this->option('limit');
        $this->info("📸 Checking sample photos (limit: {$limit})...");
        $this->checkSamplePhotos($limit);

        if ($this->option('full')) {
            $this->newLine();
            $this->info('📋 Full folder tree:');
            $this->showFullTree();
        }

        return 0;
    }

    private function checkFolderStructure(): void
    {
        try {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                $this->error('❌ Failed to get Drive instance');
                return;
            }

            // Find jalur_joint folder (case insensitive)
            $results = $drive->files->listFiles([
                'q' => "(name = 'jalur_joint' or name = 'JALUR_JOINT') and mimeType = 'application/vnd.google-apps.folder'",
                'fields' => 'files(id, name, webViewLink)'
            ]);

            if (count($results->getFiles()) === 0) {
                $this->error('❌ jalur_joint folder NOT FOUND in Google Drive!');
                $this->warn('   Expected folder name: jalur_joint or JALUR_JOINT');
                return;
            }

            $jalurJointFolder = $results->getFiles()[0];
            $this->info("✅ Found folder: {$jalurJointFolder->getName()}");
            $this->line("   Folder ID: {$jalurJointFolder->getId()}");
            $this->line("   Link: {$jalurJointFolder->getWebViewLink()}");
            $this->newLine();

            // List cluster folders
            $clusters = $drive->files->listFiles([
                'q' => "'{$jalurJointFolder->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder'",
                'fields' => 'files(id, name)',
                'pageSize' => 100,
                'orderBy' => 'name'
            ]);

            $clusterFiles = $clusters->getFiles();
            $this->info("📂 Cluster folders found: " . count($clusterFiles));

            $table = [];
            $totalJointFolders = 0;

            foreach ($clusterFiles as $cluster) {
                // Count joint folders in this cluster
                $joints = $drive->files->listFiles([
                    'q' => "'{$cluster->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder'",
                    'fields' => 'files(id)',
                    'pageSize' => 1000
                ]);

                $count = count($joints->getFiles());
                $totalJointFolders += $count;

                $table[] = [
                    'cluster' => $cluster->getName(),
                    'joint_folders' => $count
                ];
            }

            $this->table(
                ['Cluster', 'Joint Folders'],
                $table
            );

            $this->info("📊 Total joint folders in Drive: {$totalJointFolders}");

        } catch (\Exception $e) {
            $this->error('❌ Error checking folder structure: ' . $e->getMessage());
        }
    }

    private function checkSamplePhotos(int $limit): void
    {
        $photos = PhotoApproval::where('module_name', 'jalur_joint')
            ->with('jointData')
            ->take($limit)
            ->get();

        if ($photos->isEmpty()) {
            $this->warn('⚠️  No joint photos found in database');
            return;
        }

        try {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                $this->error('❌ Failed to get Drive instance');
                return;
            }

            foreach ($photos as $photo) {
                $joint = $photo->jointData;

                if (!$joint) {
                    $this->warn("⚠️  Photo #{$photo->id} has no associated joint data");
                    continue;
                }

                $this->info("Joint: {$joint->nomor_joint} - {$joint->tanggal_joint->format('Y-m-d')}");

                if (!$photo->drive_file_id) {
                    $this->error("   ❌ No Drive file ID");
                    continue;
                }

                try {
                    // Get file info
                    $file = $drive->files->get($photo->drive_file_id, [
                        'fields' => 'id, name, parents, webViewLink'
                    ]);

                    // Trace path
                    $path = $this->tracePath($drive, $photo->drive_file_id);

                    $this->line("   ✅ File: {$file->getName()}");
                    $this->line("   📂 Path: " . implode(' / ', array_reverse($path)));
                    $this->line("   🔗 Link: {$file->getWebViewLink()}");

                } catch (\Exception $e) {
                    $this->error("   ❌ Error: " . $e->getMessage());
                }

                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('❌ Error checking photos: ' . $e->getMessage());
        }
    }

    private function tracePath($drive, string $fileId, int $maxDepth = 10): array
    {
        $path = [];
        $currentId = $fileId;

        for ($i = 0; $i < $maxDepth; $i++) {
            $file = $drive->files->get($currentId, ['fields' => 'id, name, parents']);
            $path[] = $file->getName();

            $parents = $file->getParents();
            if (!$parents || count($parents) === 0) {
                break;
            }
            $currentId = $parents[0];
        }

        return $path;
    }

    private function showFullTree(): void
    {
        try {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                return;
            }

            // Find jalur_joint folder
            $results = $drive->files->listFiles([
                'q' => "(name = 'jalur_joint' or name = 'JALUR_JOINT') and mimeType = 'application/vnd.google-apps.folder'",
                'fields' => 'files(id, name)'
            ]);

            if (count($results->getFiles()) === 0) {
                return;
            }

            $jalurJointFolder = $results->getFiles()[0];

            // List all clusters
            $clusters = $drive->files->listFiles([
                'q' => "'{$jalurJointFolder->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder'",
                'fields' => 'files(id, name)',
                'pageSize' => 100,
                'orderBy' => 'name'
            ]);

            foreach ($clusters->getFiles() as $cluster) {
                $this->line("📂 {$cluster->getName()}");

                // List joints in cluster (limit to first 5)
                $joints = $drive->files->listFiles([
                    'q' => "'{$cluster->getId()}' in parents and mimeType = 'application/vnd.google-apps.folder'",
                    'fields' => 'files(id, name)',
                    'pageSize' => 5,
                    'orderBy' => 'name'
                ]);

                foreach ($joints->getFiles() as $joint) {
                    $this->line("   └── {$joint->getName()}");
                }

                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
