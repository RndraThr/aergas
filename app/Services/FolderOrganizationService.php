<?php

namespace App\Services;

use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class FolderOrganizationService
{
    public function __construct(
        private ?GoogleDriveService $googleDriveService = null
    ) {}

    /**
     * Organize photos into dedicated folders after CGP approval
     */
    public function organizePhotosAfterCgpApproval(string $reffId, string $module): array
    {
        try {
            $moduleUpper = strtoupper($module);
            $moduleSlug = strtolower($module);
            
            Log::info("Starting photo organization for CGP approved module", [
                'reff_id' => $reffId,
                'module' => $moduleUpper
            ]);

            // Get customer info
            $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
            if (!$customer) {
                throw new Exception("Customer not found for reff_id: {$reffId}");
            }

            // Get all CGP approved photos for this module
            $photos = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', $moduleSlug)
                ->where('photo_status', 'cgp_approved')
                ->get();

            if ($photos->isEmpty()) {
                throw new Exception("No CGP approved photos found for {$reffId} - {$moduleUpper}");
            }

            $results = [];
            $moved = 0;

            // Create organized folder structure
            $baseFolder = $this->buildOrganizedFolderPath($reffId, $moduleUpper, $customer->nama_pelanggan ?? null);
            
            foreach ($photos as $photo) {
                try {
                    $result = $this->movePhotoToOrganizedFolder($photo, $baseFolder);
                    if ($result['success']) {
                        $moved++;
                    }
                    $results[] = $result;
                } catch (Exception $e) {
                    Log::error("Failed to move individual photo", [
                        'photo_id' => $photo->id,
                        'reff_id' => $reffId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results[] = [
                        'success' => false,
                        'photo_id' => $photo->id,
                        'slot' => $photo->photo_field_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info("Photo organization completed", [
                'reff_id' => $reffId,
                'module' => $moduleUpper,
                'total_photos' => $photos->count(),
                'successfully_moved' => $moved,
                'base_folder' => $baseFolder
            ]);

            return [
                'success' => true,
                'reff_id' => $reffId,
                'module' => $moduleUpper,
                'customer_name' => $customer->nama_pelanggan,
                'total_photos' => $photos->count(),
                'successfully_moved' => $moved,
                'failed_moves' => $photos->count() - $moved,
                'base_folder' => $baseFolder,
                'details' => $results
            ];

        } catch (Exception $e) {
            Log::error("Photo organization failed", [
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Move single photo to organized folder structure
     */
    private function movePhotoToOrganizedFolder(PhotoApproval $photo, string $baseFolder): array
    {
        try {
            // No slot subfolders - put files directly in module folder
            $targetFolder = $baseFolder;
            
            // Generate organized filename
            $originalFilename = $photo->stored_filename ?? basename($photo->photo_url);
            $targetFilename = $this->generateOrganizedFilename($photo, $originalFilename);
            
            $moveResult = null;

            // Handle Google Drive files
            if ($photo->drive_file_id && $this->canUseDrive()) {
                $moveResult = $this->moveFileInDrive($photo, $targetFolder, $targetFilename);
            }
            // Handle local files  
            elseif ($photo->storage_path && $photo->storage_disk) {
                $moveResult = $this->moveFileLocal($photo, $targetFolder, $targetFilename);
            }
            else {
                throw new Exception("No valid file location found for photo {$photo->id}");
            }

            if ($moveResult && $moveResult['success']) {
                // Update photo record with new location
                $photo->update([
                    'storage_path' => $moveResult['new_path'],
                    'photo_url' => $moveResult['new_url'],
                    'drive_file_id' => $moveResult['new_drive_id'] ?? $photo->drive_file_id,
                    'drive_link' => $moveResult['new_drive_link'] ?? $photo->drive_link,
                    'organized_at' => now(),
                    'organized_folder' => $targetFolder
                ]);

                return [
                    'success' => true,
                    'photo_id' => $photo->id,
                    'slot' => $photo->photo_field_name,
                    'old_path' => $photo->storage_path,
                    'new_path' => $moveResult['new_path'],
                    'new_url' => $moveResult['new_url'],
                    'target_folder' => $targetFolder
                ];
            }

            throw new Exception("File move operation failed");

        } catch (Exception $e) {
            return [
                'success' => false,
                'photo_id' => $photo->id,
                'slot' => $photo->photo_field_name,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build organized folder path: aergas_approved/[REFF]__[CUSTOMER_NAME]/[MODULE]
     */
    private function buildOrganizedFolderPath(string $reffId, string $module, ?string $customerName = null): string
    {
        $customerSlug = $customerName ? $this->slugify($customerName) : '';
        $customerFolderName = $customerSlug ? "{$reffId}__{$customerSlug}" : $reffId;
        
        return "aergas_approved/{$customerFolderName}/{$module}";
    }

    /**
     * Get slot-specific subfolder name (DEPRECATED - not used anymore)
     */
    private function getSlotFolderName(string $slotName, string $module): string
    {
        // Not used anymore - files go directly in module folder
        return $this->slugify($slotName);
    }

    /**
     * Generate organized filename with timestamp
     */
    private function generateOrganizedFilename(PhotoApproval $photo, string $originalFilename): string
    {
        $timestamp = now()->format('Ymd_His');
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        
        // Remove existing timestamp patterns if any
        $baseName = preg_replace('/\d{8}_\d{6}/', '', $baseName);
        $baseName = trim($baseName, '_-');
        
        return "{$photo->reff_id_pelanggan}_{$photo->photo_field_name}_{$timestamp}.{$extension}";
    }

    /**
     * Move file in Google Drive
     */
    private function moveFileInDrive(PhotoApproval $photo, string $targetFolder, string $targetFilename): array
    {
        if (!$this->googleDriveService) {
            throw new Exception("Google Drive service not available");
        }

        // Ensure target folder exists
        $targetFolderId = $this->googleDriveService->ensureNestedFolders($targetFolder);
        
        // Move and rename file
        $result = $this->googleDriveService->moveFile($photo->drive_file_id, $targetFolderId, $targetFilename);
        
        return [
            'success' => true,
            'new_path' => "{$targetFolder}/{$targetFilename}",
            'new_url' => $result['webViewLink'] ?? $result['webContentLink'] ?? $photo->photo_url,
            'new_drive_id' => $result['id'],
            'new_drive_link' => $result['webViewLink'] ?? $result['webContentLink']
        ];
    }

    /**
     * Move file locally
     */
    private function moveFileLocal(PhotoApproval $photo, string $targetFolder, string $targetFilename): array
    {
        $disk = Storage::disk($photo->storage_disk);
        
        if (!$disk->exists($photo->storage_path)) {
            throw new Exception("Original file not found: {$photo->storage_path}");
        }

        $targetPath = "{$targetFolder}/{$targetFilename}";
        
        // Ensure target directory exists
        $disk->makeDirectory(dirname($targetPath));
        
        // Move file
        if (!$disk->move($photo->storage_path, $targetPath)) {
            throw new Exception("Failed to move file from {$photo->storage_path} to {$targetPath}");
        }

        // Generate new public URL - fix for url() method error
        $newUrl = '';
        try {
            // Use Storage facade url() method instead of disk url()
            if ($photo->storage_disk === 'public') {
                $newUrl = Storage::url($targetPath);
            } else {
                // Fallback for other disk types
                $baseUrl = rtrim((string) config('app.url'), '/');
                $normalizedPath = str_replace('\\', '/', ltrim($targetPath, '/'));
                $newUrl = "{$baseUrl}/storage/{$normalizedPath}";
            }
        } catch (Exception $e) {
            // Fallback URL construction
            $baseUrl = rtrim((string) config('app.url'), '/');
            $normalizedPath = str_replace('\\', '/', ltrim($targetPath, '/'));
            $newUrl = "{$baseUrl}/storage/{$normalizedPath}";
        }

        return [
            'success' => true,
            'new_path' => $targetPath,
            'new_url' => $newUrl
        ];
    }

    /**
     * Check if Google Drive is available
     */
    private function canUseDrive(): bool
    {
        if (!$this->googleDriveService) return false;
        $root = (string) (config('services.google_drive.folder_id') ?? '');
        return $root !== '';
    }

    /**
     * Create URL-safe slug from string
     */
    private function slugify(string $text): string
    {
        // Replace Indonesian characters
        $text = str_replace(
            ['á', 'à', 'ä', 'â', 'é', 'è', 'ë', 'ê', 'í', 'ì', 'ï', 'î', 'ó', 'ò', 'ö', 'ô', 'ú', 'ù', 'ü', 'û', 'ý', 'ÿ'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'],
            $text
        );
        
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', trim($text)));
    }
}