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

            // Keep original filename - DO NOT RENAME during organization
            // This ensures file can be reverted with same name
            $originalFilename = $photo->stored_filename ?? basename($photo->storage_path) ?? basename($photo->photo_url);
            $targetFilename = $originalFilename; // Keep original name

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
     * Revert photo organization - move file back to original upload folder
     */
    public function revertPhotoOrganization(PhotoApproval $photo): array
    {
        try {
            Log::info("Reverting photo organization", [
                'photo_id' => $photo->id,
                'reff_id' => $photo->reff_id_pelanggan,
                'module' => $photo->module_name,
                'organized_folder' => $photo->organized_folder
            ]);

            // Check if photo was organized
            if (!$photo->organized_at || !$photo->organized_folder) {
                return [
                    'success' => true,
                    'message' => 'Photo was not organized, no revert needed',
                    'photo_id' => $photo->id
                ];
            }

            // Get customer info for original folder structure
            $customer = CalonPelanggan::where('reff_id_pelanggan', $photo->reff_id_pelanggan)->first();
            if (!$customer) {
                throw new Exception("Customer not found for reff_id: {$photo->reff_id_pelanggan}");
            }

            // Build original upload folder: aergas/{MODULE}/{reff}__{customer_slug}/
            $moduleUpper = strtoupper($photo->module_name);
            $customerSlug = $customer->nama_pelanggan ? $this->slugify($customer->nama_pelanggan) : '';
            $customerFolderName = $customerSlug ? "{$photo->reff_id_pelanggan}__{$customerSlug}" : $photo->reff_id_pelanggan;
            $originalFolder = "aergas/{$moduleUpper}/{$customerFolderName}";

            // Get original filename - preserve the exact filename that was used during upload
            // Priority: 1) storage_path (has original filename), 2) photo_url
            if ($photo->storage_path) {
                $originalFilename = basename($photo->storage_path);
            } else {
                $originalFilename = basename($photo->photo_url);
            }

            // Move file back to original folder
            if ($photo->drive_file_id && $this->canUseDrive()) {
                $result = $this->moveFileInDrive($photo, $originalFolder, $originalFilename);
            } elseif ($photo->storage_path && $photo->storage_disk) {
                $result = $this->moveFileLocal($photo, $originalFolder, $originalFilename);
            } else {
                throw new Exception("No valid file location found for photo {$photo->id}");
            }

            if ($result && $result['success']) {
                // Update photo record - clear organization fields
                $photo->update([
                    'storage_path' => $result['new_path'],
                    'photo_url' => $result['new_url'],
                    'drive_file_id' => $result['new_drive_id'] ?? $photo->drive_file_id,
                    'drive_link' => $result['new_drive_link'] ?? $photo->drive_link,
                    'organized_at' => null,
                    'organized_folder' => null
                ]);

                Log::info("Photo organization reverted successfully", [
                    'photo_id' => $photo->id,
                    'old_folder' => $photo->organized_folder,
                    'new_folder' => $originalFolder,
                    'new_url' => $result['new_url']
                ]);

                return [
                    'success' => true,
                    'message' => 'Photo organization reverted successfully',
                    'photo_id' => $photo->id,
                    'old_folder' => $photo->organized_folder,
                    'new_folder' => $originalFolder,
                    'new_url' => $result['new_url']
                ];
            }

            throw new Exception("File move operation failed during revert");

        } catch (Exception $e) {
            Log::error("Failed to revert photo organization", [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Organize jalur photos into dedicated folders after CGP approval
     * Structure: aergas_approved_jalur/{CLUSTER}/{LINE_NUMBER}/{DATE}/{LOWERING|JOINT}/
     */
    public function organizeJalurPhotosAfterCgpApproval(int $lineId, string $date, string $moduleType): array
    {
        try {
            Log::info("Starting jalur photo organization for CGP approved module", [
                'line_id' => $lineId,
                'date' => $date,
                'module_type' => $moduleType
            ]);

            // Get line number info
            $lineNumber = \App\Models\JalurLineNumber::with('cluster')->find($lineId);
            if (!$lineNumber) {
                throw new Exception("Line number not found: {$lineId}");
            }

            // Get all CGP approved photos for this line on this date
            $photos = PhotoApproval::where('module_name', $moduleType)
                ->where('module_record_id', function($query) use ($lineId, $date, $moduleType) {
                    if ($moduleType === 'jalur_lowering') {
                        $query->select('id')
                            ->from('jalur_lowering_data')
                            ->where('line_number_id', $lineId)
                            ->whereDate('tanggal_jalur', $date);
                    } else {
                        $query->select('id')
                            ->from('jalur_joint_data')
                            ->whereHas('lineNumber', function($q) use ($lineId) {
                                $q->where('id', $lineId);
                            })
                            ->whereDate('tanggal_joint', $date);
                    }
                })
                ->where('photo_status', 'cgp_approved')
                ->get();

            if ($photos->isEmpty()) {
                throw new Exception("No CGP approved photos found for line {$lineId} on {$date}");
            }

            $results = [];
            $moved = 0;

            // Build organized folder structure
            $baseFolder = $this->buildJalurOrganizedFolderPath(
                $lineNumber->cluster->nama_cluster,
                $lineNumber->line_number,
                $date,
                $moduleType
            );

            foreach ($photos as $photo) {
                try {
                    $result = $this->movePhotoToOrganizedFolder($photo, $baseFolder);
                    if ($result['success']) {
                        $moved++;
                    }
                    $results[] = $result;
                } catch (Exception $e) {
                    Log::error("Failed to move individual jalur photo", [
                        'photo_id' => $photo->id,
                        'line_id' => $lineId,
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

            Log::info("Jalur photo organization completed", [
                'line_id' => $lineId,
                'date' => $date,
                'module_type' => $moduleType,
                'total_photos' => $photos->count(),
                'successfully_moved' => $moved,
                'base_folder' => $baseFolder
            ]);

            return [
                'success' => true,
                'line_id' => $lineId,
                'line_number' => $lineNumber->line_number,
                'cluster' => $lineNumber->cluster->nama_cluster,
                'date' => $date,
                'module_type' => $moduleType,
                'total_photos' => $photos->count(),
                'successfully_moved' => $moved,
                'failed_moves' => $photos->count() - $moved,
                'base_folder' => $baseFolder,
                'details' => $results
            ];

        } catch (Exception $e) {
            Log::error("Jalur photo organization failed", [
                'line_id' => $lineId,
                'date' => $date,
                'module_type' => $moduleType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'line_id' => $lineId,
                'date' => $date,
                'module_type' => $moduleType,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build organized folder path for jalur photos
     * Structure: aergas_approved_jalur/{CLUSTER}/{LINE_NUMBER}/{DATE}/{LOWERING|JOINT}
     */
    private function buildJalurOrganizedFolderPath(string $clusterName, string $lineNumber, string $date, string $moduleType): string
    {
        $clusterSlug = $this->slugify($clusterName);
        $moduleFolder = $moduleType === 'jalur_lowering' ? 'LOWERING' : 'JOINT';

        return "aergas_approved_jalur/{$clusterSlug}/{$lineNumber}/{$date}/{$moduleFolder}";
    }

    /**
     * Revert jalur photo organization - move file back to original upload folder
     */
    public function revertJalurPhotoOrganization(PhotoApproval $photo): array
    {
        try {
            Log::info("Reverting jalur photo organization", [
                'photo_id' => $photo->id,
                'module_name' => $photo->module_name,
                'organized_folder' => $photo->organized_folder
            ]);

            // Check if photo was organized
            if (!$photo->organized_at || !$photo->organized_folder) {
                return [
                    'success' => true,
                    'message' => 'Photo was not organized, no revert needed',
                    'photo_id' => $photo->id
                ];
            }

            // Get module data to reconstruct original folder
            if ($photo->module_name === 'jalur_lowering') {
                $moduleData = \App\Models\JalurLoweringData::with('lineNumber.cluster')->find($photo->module_record_id);
            } else {
                $moduleData = \App\Models\JalurJointData::with('cluster')->find($photo->module_record_id);
            }

            if (!$moduleData) {
                throw new Exception("Module data not found for photo {$photo->id}");
            }

            // Build original upload folder
            $moduleFolder = strtoupper(str_replace('jalur_', '', $photo->module_name));

            if ($photo->module_name === 'jalur_lowering') {
                $clusterSlug = $this->slugify($moduleData->lineNumber->cluster->nama_cluster);
                $lineNumber = $moduleData->lineNumber->line_number;
                $date = $moduleData->tanggal_jalur->format('Y-m-d');
            } else {
                $clusterSlug = $this->slugify($moduleData->cluster->nama_cluster);
                $lineNumber = $moduleData->nomor_joint;
                $date = $moduleData->tanggal_joint->format('Y-m-d');
            }

            $originalFolder = "aergas/JALUR_{$moduleFolder}/{$clusterSlug}/{$lineNumber}/{$date}";

            // Get original filename
            if ($photo->storage_path) {
                $originalFilename = basename($photo->storage_path);
            } else {
                $originalFilename = basename($photo->photo_url);
            }

            // Move file back to original folder
            if ($photo->drive_file_id && $this->canUseDrive()) {
                $result = $this->moveFileInDrive($photo, $originalFolder, $originalFilename);
            } elseif ($photo->storage_path && $photo->storage_disk) {
                $result = $this->moveFileLocal($photo, $originalFolder, $originalFilename);
            } else {
                throw new Exception("No valid file location found for photo {$photo->id}");
            }

            if ($result && $result['success']) {
                // Update photo record - clear organization fields
                $photo->update([
                    'storage_path' => $result['new_path'],
                    'photo_url' => $result['new_url'],
                    'drive_file_id' => $result['new_drive_id'] ?? $photo->drive_file_id,
                    'drive_link' => $result['new_drive_link'] ?? $photo->drive_link,
                    'organized_at' => null,
                    'organized_folder' => null
                ]);

                Log::info("Jalur photo organization reverted successfully", [
                    'photo_id' => $photo->id,
                    'old_folder' => $photo->organized_folder,
                    'new_folder' => $originalFolder,
                    'new_url' => $result['new_url']
                ]);

                return [
                    'success' => true,
                    'message' => 'Jalur photo organization reverted successfully',
                    'photo_id' => $photo->id,
                    'old_folder' => $photo->organized_folder,
                    'new_folder' => $originalFolder,
                    'new_url' => $result['new_url']
                ];
            }

            throw new Exception("File move operation failed during revert");

        } catch (Exception $e) {
            Log::error("Failed to revert jalur photo organization", [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ];
        }
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