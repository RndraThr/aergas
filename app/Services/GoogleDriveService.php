<?php
namespace App\Services;

use Google\Client as Google_Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
    private Google_Client $client;
    private Drive $driveService;
    private string $folderId;

    public function __construct()
    {
        $this->initializeClient();
        $this->driveService = new Drive($this->client);
        $this->folderId = config('services.google_drive.folder_id');
    }

    /**
     * Initialize Google Drive client
     *
     * @return void
     * @throws Exception
     */
    private function initializeClient(): void
    {
        $this->client = new Google_Client();

        try {
            // Method 1: Using Service Account JSON (Recommended for server-to-server)
            $serviceAccountPath = config('services.google_drive.service_account_json');
            if ($serviceAccountPath && file_exists($serviceAccountPath)) {
                $this->client->setAuthConfig($serviceAccountPath);
                $this->client->addScope(Drive::DRIVE_FILE);
                Log::info('Google Drive initialized with Service Account');
                return;
            }

            // Method 2: Using OAuth credentials
            $this->client->setClientId(config('services.google_drive.client_id'));
            $this->client->setClientSecret(config('services.google_drive.client_secret'));
            $this->client->refreshToken(config('services.google_drive.refresh_token'));
            $this->client->addScope(Drive::DRIVE_FILE);

            Log::info('Google Drive initialized with OAuth');

        } catch (Exception $e) {
            Log::error('Failed to initialize Google Drive client', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Google Drive initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to Google Drive
     *
     * @param string $localFilePath
     * @param string $fileName
     * @param string $reffId
     * @param string $module
     * @param string $photoField
     * @return array
     * @throws Exception
     */
    public function uploadFile(
        string $localFilePath,
        string $fileName,
        string $reffId,
        string $module,
        string $photoField
    ): array {
        try {
            // Verify file exists
            if (!file_exists($localFilePath)) {
                throw new Exception("Local file not found: {$localFilePath}");
            }

            // Create folder structure if not exists
            $moduleFolderId = $this->createFolderIfNotExists($module, $this->folderId);
            $customerFolderId = $this->createFolderIfNotExists($reffId, $moduleFolderId);

            // Prepare file metadata
            $fileMetadata = new DriveFile([
                'name' => $fileName,
                'parents' => [$customerFolderId],
                'description' => "AERGAS - {$module} - {$photoField} - {$reffId}"
            ]);

            // Get file content and mime type
            $content = file_get_contents($localFilePath);
            $mimeType = mime_content_type($localFilePath);

            Log::info('Uploading file to Google Drive', [
                'file_name' => $fileName,
                'reff_id' => $reffId,
                'module' => $module,
                'size' => strlen($content),
                'mime_type' => $mimeType
            ]);

            // Upload file
            $file = $this->driveService->files->create(
                $fileMetadata,
                [
                    'data' => $content,
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart'
                ]
            );

            // Make file shareable (view-only)
            $this->driveService->permissions->create(
                $file->id,
                new Permission([
                    'role' => 'reader',
                    'type' => 'anyone'
                ])
            );

            $result = [
                'google_drive_id' => $file->id,
                'google_drive_url' => "https://drive.google.com/file/d/{$file->id}/view",
                'direct_url' => "https://drive.google.com/uc?id={$file->id}",
                'file_name' => $fileName,
                'file_size' => strlen($content),
                'mime_type' => $mimeType,
                'uploaded_at' => now()->toISOString()
            ];

            Log::info('File uploaded successfully to Google Drive', $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Google Drive upload failed', [
                'file_name' => $fileName,
                'reff_id' => $reffId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception("Google Drive upload failed: {$e->getMessage()}");
        }
    }

    /**
     * Delete file from Google Drive
     *
     * @param string $fileId
     * @return bool
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->driveService->files->delete($fileId);

            Log::info('File deleted from Google Drive', [
                'file_id' => $fileId
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete file from Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Create folder if not exists
     *
     * @param string $folderName
     * @param string $parentFolderId
     * @return string
     * @throws Exception
     */
    private function createFolderIfNotExists(string $folderName, string $parentFolderId): string
    {
        try {
            // Check if folder already exists
            $existing = $this->driveService->files->listFiles([
                'q' => "name='{$folderName}' and parents in '{$parentFolderId}' and mimeType='application/vnd.google-apps.folder' and trashed=false",
                'fields' => 'files(id, name)'
            ]);

            if (!empty($existing->getFiles())) {
                return $existing->getFiles()[0]->getId();
            }

            // Create new folder
            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId]
            ]);

            $folder = $this->driveService->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            Log::info('Created Google Drive folder', [
                'folder_name' => $folderName,
                'folder_id' => $folder->id,
                'parent_id' => $parentFolderId
            ]);

            return $folder->id;

        } catch (Exception $e) {
            Log::error('Failed to create Google Drive folder', [
                'folder_name' => $folderName,
                'parent_id' => $parentFolderId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get file info from Google Drive
     *
     * @param string $fileId
     * @return array|null
     */
    public function getFileInfo(string $fileId): ?array
    {
        try {
            $file = $this->driveService->files->get($fileId, [
                'fields' => 'id, name, size, mimeType, createdTime, modifiedTime, webViewLink, webContentLink'
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_at' => $file->getCreatedTime(),
                'modified_at' => $file->getModifiedTime(),
                'view_link' => $file->getWebViewLink(),
                'download_link' => $file->getWebContentLink()
            ];

        } catch (Exception $e) {
            Log::error('Failed to get file info from Google Drive', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * List files in folder
     *
     * @param string $folderId
     * @param int $limit
     * @return array
     */
    public function listFiles(string $folderId, int $limit = 100): array
    {
        try {
            $files = $this->driveService->files->listFiles([
                'q' => "parents in '{$folderId}' and trashed=false",
                'pageSize' => $limit,
                'fields' => 'files(id, name, size, mimeType, createdTime, modifiedTime)',
                'orderBy' => 'createdTime desc'
            ]);

            $fileList = [];
            foreach ($files->getFiles() as $file) {
                $fileList[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'created_at' => $file->getCreatedTime(),
                    'modified_at' => $file->getModifiedTime()
                ];
            }

            return $fileList;

        } catch (Exception $e) {
            Log::error('Failed to list Google Drive files', [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Test Google Drive connection
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            // Test by getting drive info
            $about = $this->driveService->about->get(['fields' => 'user, storageQuota']);

            return [
                'success' => true,
                'message' => 'Google Drive connection successful',
                'user_email' => $about->getUser()->getEmailAddress(),
                'storage_used' => $about->getStorageQuota()->getUsage(),
                'storage_limit' => $about->getStorageQuota()->getLimit(),
                'folder_id' => $this->folderId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Google Drive connection failed',
                'error' => $e->getMessage()
            ];
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
            $about = $this->driveService->about->get(['fields' => 'storageQuota']);
            $quota = $about->getStorageQuota();

            $used = (int) $quota->getUsage();
            $limit = (int) $quota->getLimit();
            $usedPercent = $limit > 0 ? round(($used / $limit) * 100, 2) : 0;

            return [
                'used_bytes' => $used,
                'limit_bytes' => $limit,
                'used_human' => $this->formatBytes($used),
                'limit_human' => $this->formatBytes($limit),
                'used_percent' => $usedPercent,
                'available_bytes' => $limit - $used,
                'available_human' => $this->formatBytes($limit - $used)
            ];

        } catch (Exception $e) {
            Log::error('Failed to get Google Drive storage stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'used_bytes' => 0,
                'limit_bytes' => 0,
                'used_human' => '0 B',
                'limit_human' => '0 B',
                'used_percent' => 0,
                'available_bytes' => 0,
                'available_human' => '0 B'
            ];
        }
    }

    /**
     * Format bytes to human readable
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
}
