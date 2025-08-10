<?php

namespace App\Services;

use App\Models\FileStorage;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    private GoogleDriveService $googleDriveService;

    private array $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/jpg',
        'image/gif', 'image/webp', 'application/pdf'
    ];

    private int $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Upload photo file with Google Drive integration
     *
     * @param UploadedFile $file
     * @param string $reffId
     * @param string $module
     * @param string $fieldName
     * @param int $uploadedBy
     * @return array
     * @throws Exception
     */
    public function uploadPhoto(
        UploadedFile $file,
        string $reffId,
        string $module,
        string $fieldName,
        int $uploadedBy
    ): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('YmdHis');
            $storedName = "{$module}_{$fieldName}_{$reffId}_{$timestamp}.{$extension}";

            // Create local directory path
            $directory = "public/{$module}/{$reffId}";

            // Ensure directory exists
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Delete existing file if exists
            $this->deleteExistingPhoto($reffId, $module, $fieldName);

            // Store file locally first
            $localPath = $file->storeAs($directory, $storedName);
            $localUrl = Storage::url($localPath);
            $fullLocalPath = Storage::path($localPath);

            // Calculate file hash for duplicate detection
            $fileHash = hash_file('sha256', $file->getRealPath());

            Log::info('Starting Google Drive upload', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName,
                'local_path' => $localPath
            ]);

            // Upload to Google Drive
            $googleDriveResult = $this->googleDriveService->uploadFile(
                $fullLocalPath,
                $storedName,
                $reffId,
                $module,
                $fieldName
            );

            // Save to database with Google Drive info
            $fileStorage = FileStorage::create([
                'reff_id_pelanggan' => $reffId,
                'module_name' => $module,
                'field_name' => $fieldName,
                'original_filename' => $originalName,
                'stored_filename' => $storedName,
                'file_path' => $localPath,
                'google_drive_id' => $googleDriveResult['google_drive_id'],
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'uploaded_by' => $uploadedBy
            ]);

            Log::info('Photo uploaded successfully with Google Drive', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName,
                'google_drive_id' => $googleDriveResult['google_drive_id'],
                'file_size' => $file->getSize(),
                'uploaded_by' => $uploadedBy
            ]);

            return [
                'success' => true,
                'url' => $localUrl, // Still use local URL for immediate access
                'path' => $localPath,
                'file_storage' => $fileStorage,
                'google_drive' => $googleDriveResult,
                'file_info' => [
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension
                ]
            ];

        } catch (Exception $e) {
            Log::error('Photo upload failed', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName,
                'error' => $e->getMessage()
            ]);

            throw new Exception("Failed to upload photo: {$e->getMessage()}");
        }
    }

    /**
     * Delete existing photo from both local and Google Drive
     *
     * @param string $reffId
     * @param string $module
     * @param string $fieldName
     * @return bool
     */
    public function deleteExistingPhoto(string $reffId, string $module, string $fieldName): bool
    {
        try {
            $fileStorage = FileStorage::where([
                'reff_id_pelanggan' => $reffId,
                'module_name' => $module,
                'field_name' => $fieldName
            ])->first();

            if (!$fileStorage) {
                return true; // No existing file
            }

            // Delete from Google Drive
            if ($fileStorage->google_drive_id) {
                $this->googleDriveService->deleteFile($fileStorage->google_drive_id);
                Log::info('File deleted from Google Drive', [
                    'google_drive_id' => $fileStorage->google_drive_id
                ]);
            }

            // Delete physical file
            if (Storage::exists($fileStorage->file_path)) {
                Storage::delete($fileStorage->file_path);
                Log::info('Physical file deleted', [
                    'file_path' => $fileStorage->file_path
                ]);
            }

            // Delete database record
            $fileStorage->delete();

            Log::info('Existing photo deleted completely', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete existing photo', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get file information with Google Drive details
     *
     * @param string $reffId
     * @param string $module
     * @param string $fieldName
     * @return array|null
     */
    public function getFileInfo(string $reffId, string $module, string $fieldName): ?array
    {
        try {
            $fileStorage = FileStorage::with('uploadedByUser')->where([
                'reff_id_pelanggan' => $reffId,
                'module_name' => $module,
                'field_name' => $fieldName
            ])->first();

            if (!$fileStorage) {
                return null;
            }

            $result = [
                'id' => $fileStorage->id,
                'original_filename' => $fileStorage->original_filename,
                'stored_filename' => $fileStorage->stored_filename,
                'file_path' => $fileStorage->file_path,
                'url' => Storage::url($fileStorage->file_path),
                'mime_type' => $fileStorage->mime_type,
                'file_size' => $fileStorage->file_size,
                'file_size_human' => $fileStorage->getFileSizeHuman(),
                'uploaded_by' => $fileStorage->uploadedByUser->full_name ?? 'Unknown',
                'uploaded_at' => $fileStorage->created_at,
                'is_image' => $fileStorage->isImage(),
                'is_pdf' => $fileStorage->isPdf(),
                'google_drive_id' => $fileStorage->google_drive_id,
            ];

            // Get Google Drive info if available
            if ($fileStorage->google_drive_id) {
                $googleDriveInfo = $this->googleDriveService->getFileInfo($fileStorage->google_drive_id);
                $result['google_drive'] = $googleDriveInfo;
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to get file info', [
                'reff_id' => $reffId,
                'module' => $module,
                'field_name' => $fieldName,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get storage statistics
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        try {
            $stats = [
                'total_files' => FileStorage::count(),
                'total_size' => FileStorage::sum('file_size'),
                'files_by_module' => FileStorage::selectRaw('module_name, COUNT(*) as count, SUM(file_size) as total_size')
                                               ->groupBy('module_name')
                                               ->get()
                                               ->keyBy('module_name')
                                               ->toArray(),
                'files_by_mime_type' => FileStorage::selectRaw('mime_type, COUNT(*) as count')
                                                   ->groupBy('mime_type')
                                                   ->get()
                                                   ->keyBy('mime_type')
                                                   ->toArray(),
                'recent_uploads' => FileStorage::with('uploadedByUser')
                                               ->orderBy('created_at', 'desc')
                                               ->take(10)
                                               ->get(),
            ];

            // Convert bytes to human readable format
            $stats['total_size_human'] = $this->formatBytes($stats['total_size']);

            return $stats;

        } catch (Exception $e) {
            Log::error('Failed to get storage stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_files' => 0,
                'total_size' => 0,
                'total_size_human' => '0 B',
                'files_by_module' => [],
                'files_by_mime_type' => [],
                'recent_uploads' => []
            ];
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clean up orphaned files
     *
     * @return array
     */
    public function cleanupOrphanedFiles(): array
    {
        try {
            $deletedFiles = 0;
            $deletedSize = 0;
            $errors = [];

            // Find file storage records without physical files
            $fileStorages = FileStorage::all();

            foreach ($fileStorages as $fileStorage) {
                if (!Storage::exists($fileStorage->file_path)) {
                    try {
                        $deletedSize += $fileStorage->file_size;
                        $fileStorage->delete();
                        $deletedFiles++;

                        Log::info('Orphaned file record cleaned up', [
                            'id' => $fileStorage->id,
                            'file_path' => $fileStorage->file_path
                        ]);

                    } catch (Exception $e) {
                        $errors[] = "Failed to clean up file record {$fileStorage->id}: {$e->getMessage()}";
                    }
                }
            }

            return [
                'deleted_files' => $deletedFiles,
                'deleted_size' => $deletedSize,
                'deleted_size_human' => $this->formatBytes($deletedSize),
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error('Failed to cleanup orphaned files', [
                'error' => $e->getMessage()
            ]);

            return [
                'deleted_files' => 0,
                'deleted_size' => 0,
                'deleted_size_human' => '0 B',
                'errors' => [$e->getMessage()]
            ];
        }
    }
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / (1024 * 1024);
            throw new Exception("File size exceeds maximum limit of {$maxSizeMB}MB");
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception("Invalid file type: {$mimeType}");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("Invalid file extension: {$extension}");
        }
    }
}
