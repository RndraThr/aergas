<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Exception;

class GoogleDriveService
{
    private ?Drive $drive = null;
    private ?GoogleClient $client = null;
    private ?string $rootFolderId = null;
    private bool $initialized = false;
    private ?Exception $initializationError = null;

    public function __construct()
    {
        // Don't initialize immediately - use lazy initialization
        $this->rootFolderId = config('services.google_drive.folder_id');
    }

    /**
     * Lazy initialization with error handling
     */
    private function initialize(): bool
    {
        if ($this->initialized) {
            return $this->drive !== null;
        }

        $this->initialized = true;

        try {
            if (!config('services.google_drive.enabled', true)) {
                throw new Exception('Google Drive integration is disabled');
            }

            if (!$this->rootFolderId) {
                throw new Exception('GOOGLE_DRIVE_FOLDER_ID is required.');
            }

            $this->client = new GoogleClient();
            $this->client->setApplicationName('AERGAS');
            $this->client->setScopes([Drive::DRIVE]);

            $this->authenticateClient($this->client);
            $this->drive = new Drive($this->client);

            Log::info('Google Drive service initialized successfully');
            return true;

        } catch (Exception $e) {
            $this->initializationError = $e;
            Log::error('Google Drive service initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return $this->initialize();
    }

    /**
     * Get initialization error if any
     */
    public function getError(): ?string
    {
        return $this->initializationError?->getMessage();
    }

    private function authenticateClient(GoogleClient $client): void
    {
        $saJsonPath = config('services.google_drive.service_account_json');

        if ($saJsonPath) {
            $jsonPath = $this->resolveServiceAccountPath($saJsonPath);
            if ($jsonPath && file_exists($jsonPath)) {
                $client->setAuthConfig($jsonPath);
                Log::info('Google Drive authenticated with service account');
                return;
            }
        }

        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if ($clientId && $clientSecret && $refreshToken) {
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);

            $token = $this->refreshTokenWithRetry($client, $refreshToken);
            $client->setAccessToken($token);
            Log::info('Google Drive authenticated with OAuth');
            return;
        }

        throw new Exception('Missing Google Drive credentials. Please configure service account JSON or OAuth credentials.');
    }

    private function resolveServiceAccountPath(string $path): ?string
    {
        $possiblePaths = [
            $path,
            storage_path($path),
            storage_path('app/' . $path),
            base_path($path),
        ];

        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function refreshTokenWithRetry(GoogleClient $client, string $refreshToken, int $maxRetries = 3): array
    {
        $cacheKey = 'google_drive_access_token';
        
        // Check if we have a cached valid token
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
            if (time() < $cachedToken['expires_at'] - 300) { // 5 minutes buffer
                Log::info('Using cached Google Drive access token');
                return $cachedToken;
            }
        }

        $lastException = null;

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                if (!empty($token['error'])) {
                    $error = $token['error_description'] ?? $token['error'];
                    throw new Exception('Failed to fetch access token: ' . $error);
                }

                // Add expires_at timestamp for easier checking
                $token['expires_at'] = time() + ($token['expires_in'] ?? 3600);
                
                // Cache the token for slightly less than its expiration time
                $cacheMinutes = floor(($token['expires_in'] ?? 3600) / 60) - 5; // 5 minutes buffer
                Cache::put($cacheKey, $token, now()->addMinutes(max($cacheMinutes, 30)));

                Log::info('Google Drive token refreshed and cached successfully', [
                    'attempt' => $i + 1,
                    'expires_in' => $token['expires_in'] ?? 'unknown',
                    'cached_for_minutes' => $cacheMinutes
                ]);
                return $token;

            } catch (Exception $e) {
                $lastException = $e;
                Log::warning('Token refresh failed', ['attempt' => $i + 1, 'error' => $e->getMessage()]);

                if ($i < $maxRetries - 1) {
                    sleep(2 ** $i);
                }
            }
        }

        // Clear any invalid cached token
        Cache::forget($cacheKey);
        throw $lastException;
    }

    public function ensureNestedFolders(string $path): string
    {
        if (!$this->initialize()) {
            throw new Exception('Google Drive service not available: ' . $this->getError());
        }

        $path = trim($path, "/ \t\n\r\0\x0B");
        if ($path === '') {
            return $this->rootFolderId;
        }

        $parts = array_values(array_filter(explode('/', $path), fn ($v) => $v !== ''));

        $parentId = $this->rootFolderId;
        foreach ($parts as $name) {
            $parentId = $this->ensureFolder($parentId, $name);
        }
        return $parentId;
    }

    public function uploadFile(UploadedFile $file, string $folderId, ?string $targetName = null): array
    {
        if (!$this->initialize()) {
            throw new Exception('Google Drive service not available: ' . $this->getError());
        }

        $name = $targetName ?: $file->getClientOriginalName();
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $driveFile = new DriveFile([
            'name'    => $name,
            'parents' => [$folderId],
        ]);

        $created = $this->drive->files->create(
            $driveFile,
            [
                'data'       => file_get_contents($file->getRealPath()),
                'mimeType'   => $mime,
                'uploadType' => 'multipart',
                'fields'     => 'id,name,webViewLink,webContentLink',
                'supportsAllDrives' => true,
            ]
        );

        try {
            $perm = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->drive->permissions->create($created->id, $perm, [
                'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Set permission failed (non fatal): ' . $e->getMessage());
        }

        return [
            'id'             => $created->id,
            'name'           => $created->name ?? $name,
            'webViewLink'    => $created->webViewLink ?? null,
            'webContentLink' => $created->webContentLink ?? null,
        ];
    }

    /**
     * Move file to a different folder and optionally rename
     */
    public function moveFile(string $fileId, string $targetFolderId, ?string $newName = null): array
    {
        if (!$this->initialize()) {
            throw new Exception('Google Drive service not available: ' . $this->getError());
        }

        try {
            // Get current file info
            $file = $this->drive->files->get($fileId, [
                'fields' => 'id,name,parents,webViewLink,webContentLink',
                'supportsAllDrives' => true
            ]);

            $updateData = [];
            $updateParams = ['supportsAllDrives' => true, 'fields' => 'id,name,webViewLink,webContentLink'];

            // Update parent folders if needed
            if ($targetFolderId !== ($file->getParents()[0] ?? null)) {
                $currentParents = implode(',', $file->getParents() ?? []);
                $updateParams['addParents'] = $targetFolderId;
                $updateParams['removeParents'] = $currentParents;
            }

            // Update name if needed
            if ($newName && $newName !== $file->getName()) {
                $updateData['name'] = $newName;
            }

            // Perform update only if there are changes
            if (!empty($updateData) || isset($updateParams['addParents'])) {
                $driveFile = new DriveFile($updateData);
                $updated = $this->drive->files->update($fileId, $driveFile, $updateParams);
                
                Log::info('File moved successfully in Google Drive', [
                    'file_id' => $fileId,
                    'old_name' => $file->getName(),
                    'new_name' => $updated->getName(),
                    'target_folder_id' => $targetFolderId
                ]);

                return [
                    'id' => $updated->getId(),
                    'name' => $updated->getName(),
                    'webViewLink' => $updated->getWebViewLink(),
                    'webContentLink' => $updated->getWebContentLink(),
                ];
            }

            // No changes needed, return current info
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'webViewLink' => $file->getWebViewLink(),
                'webContentLink' => $file->getWebContentLink(),
            ];

        } catch (\Google\Service\Exception $e) {
            Log::error('Google Drive API error during move', [
                'file_id' => $fileId,
                'target_folder_id' => $targetFolderId,
                'new_name' => $newName,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
            throw new Exception('Failed to move file in Google Drive: ' . $e->getMessage());
        }
    }

    public function mirrorToDrive(string $disk, string $path, string $module, string $reffId, string $sub = 'original'): array
    {
        if (!$this->initialize()) {
            throw new Exception('Google Drive service not available: ' . $this->getError());
        }

        $abs = Storage::disk($disk)->path($path);
        if (!is_file($abs)) {
            throw new Exception("Local file not found: {$abs}");
        }

        $moduleFolderName = strtoupper($module) . '_Data';
        $folderModuleId   = $this->ensureFolder($this->rootFolderId, $moduleFolderName);
        $folderReffId     = $this->ensureFolder($folderModuleId, $reffId);
        $folderSubId      = $this->ensureFolder($folderReffId, $sub);

        $file = new DriveFile([
            'name'    => basename($abs),
            'parents' => [$folderSubId],
        ]);

        $mime = mime_content_type($abs) ?: 'application/octet-stream';
        $created = $this->drive->files->create($file, [
            'data'       => file_get_contents($abs),
            'mimeType'   => $mime,
            'uploadType' => 'media',
            'fields'     => 'id,webViewLink',
            'supportsAllDrives' => true,
        ]);

        try {
            $perm = new Permission(['type' => 'anyone', 'role' => 'reader']);
            $this->drive->permissions->create($created->id, $perm, [
                'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Set permission failed (non fatal): ' . $e->getMessage());
        }

        return [$created->id, $created->webViewLink ?? null];
    }

    public function deleteFile(string $fileId): void
    {
        if (!$this->initialize()) {
            Log::warning('Google Drive service not available for delete operation', ['file_id' => $fileId, 'error' => $this->getError()]);
            return;
        }

        try {
            $this->drive->files->delete($fileId, [
                'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Drive delete failed', ['id' => $fileId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Delete folder by path
     */
    public function deleteFolder(string $folderPath): bool
    {
        if (!$this->initialize()) {
            Log::warning('Google Drive service not available for delete folder operation', ['folder_path' => $folderPath, 'error' => $this->getError()]);
            return false;
        }

        try {
            $folderId = $this->getFolderIdByPath($folderPath);
            if (!$folderId) {
                Log::info('Folder not found, skipping deletion', ['folder_path' => $folderPath]);
                return false;
            }

            // Delete the folder (this will also delete all files inside)
            $this->drive->files->delete($folderId, [
                'supportsAllDrives' => true,
            ]);

            Log::info('Deleted folder from Google Drive', ['folder_path' => $folderPath, 'folder_id' => $folderId]);
            return true;

        } catch (\Throwable $e) {
            Log::warning('Google Drive folder deletion failed', ['folder_path' => $folderPath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get folder ID by path
     */
    private function getFolderIdByPath(string $path): ?string
    {
        try {
            $path = trim($path, "/ \t\n\r\0\x0B");
            if ($path === '') {
                return $this->rootFolderId;
            }

            $parts = array_values(array_filter(explode('/', $path), fn ($v) => $v !== ''));
            $parentId = $this->rootFolderId;

            foreach ($parts as $name) {
                $quoted = addcslashes($name, "'\\");
                $q = "mimeType='application/vnd.google-apps.folder' and name='{$quoted}' and '{$parentId}' in parents and trashed=false";

                $list = $this->drive->files->listFiles([
                    'q'      => $q,
                    'fields' => 'files(id,name)',
                    'pageSize' => 1,
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ]);

                $files = $list->getFiles();
                if (empty($files)) {
                    return null; // Folder not found
                }

                $parentId = $files[0]->getId();
            }

            return $parentId;

        } catch (\Throwable $e) {
            Log::error('Error getting folder ID by path', ['path' => $path, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function testConnection(): array
    {
        if (!$this->initialize()) {
            return [
                'success' => false,
                'message' => $this->getError() ?? 'Failed to initialize Google Drive service',
                'auth_method' => config('services.google_drive.service_account_json') ? 'service_account' : 'oauth',
            ];
        }

        try {
            $about = $this->drive->about->get(['fields' => 'user,storageQuota']);
            $user  = $about->getUser();
            $quota = $about->getStorageQuota();

            return [
                'success'       => true,
                'message'       => 'Connected to Google Drive successfully',
                'user_email'    => $user?->getEmailAddress(),
                'display_name'  => $user?->getDisplayName(),
                'storage_used'  => $quota?->getUsage(),
                'storage_limit' => $quota?->getLimit(),
                'auth_method'   => config('services.google_drive.service_account_json') ? 'service_account' : 'oauth',
            ];
        } catch (\Throwable $e) {
            Log::error('Google Drive test connection failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'auth_method' => config('services.google_drive.service_account_json') ? 'service_account' : 'oauth',
            ];
        }
    }

    public function getStorageStats(): array
    {
        if (!$this->initialize()) {
            return [
                'used' => 0,
                'limit' => null,
                'used_human' => 'Service Unavailable',
                'limit_human' => 'Service Unavailable',
                'percentage' => 0,
                'folders' => [],
                'error' => $this->getError()
            ];
        }

        try {
            $about = $this->drive->about->get(['fields' => 'storageQuota']);
            $quota = $about->getStorageQuota();

            $used = (int) ($quota?->getUsage() ?? 0);
            $limit = $quota?->getLimit();

            return [
                'used'        => $used,
                'limit'       => $limit,
                'used_human'  => $this->formatBytes($used),
                'limit_human' => $limit ? $this->formatBytes($limit) : 'Unlimited',
                'percentage'  => $limit ? round(($used / $limit) * 100, 2) : 0,
                'folders'     => [],
            ];
        } catch (\Throwable $e) {
            Log::error('Google Drive storage stats failed', ['error' => $e->getMessage()]);
            return [
                'used' => 0,
                'limit' => null,
                'used_human' => 'Unavailable',
                'limit_human' => 'Unavailable',
                'percentage' => 0,
                'folders' => []
            ];
        }
    }

    private function ensureFolder(string $parentId, string $name): string
    {
        $quoted = addcslashes($name, "'\\");
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$quoted}' and '{$parentId}' in parents and trashed=false";

        $list = $this->drive->files->listFiles([
            'q'      => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $list->getFiles();
        if (!empty($files)) {
            return $files[0]->getId();
        }

        $folder = new DriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentId],
        ]);

        $created = $this->drive->files->create($folder, [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        return $created->id;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB','TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }

    /**
     * Copy/download file from Google Drive link to our project folder
     */
    public function copyFromDriveLink(string $driveLink, string $customPath = '', string $customFileName = ''): array
    {
        if (!$this->initialize()) {
            throw $this->initializationError ?? new Exception('Google Drive service not initialized');
        }

        try {
            // Extract file ID from Google Drive link
            $fileId = $this->extractFileIdFromLink($driveLink);
            if (!$fileId) {
                throw new Exception('Cannot extract file ID from Google Drive link');
            }

            // Get file metadata
            $file = $this->drive->files->get($fileId, [
                'fields' => 'id,name,mimeType,size',
                'supportsAllDrives' => true
            ]);

            // Check if it's an image
            if (!str_starts_with($file->mimeType, 'image/')) {
                throw new Exception('File is not an image: ' . $file->mimeType);
            }

            // Download file content
            $response = $this->drive->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true
            ]);

            // Google Drive API returns content directly, not wrapped in response object
            $content = $response;
            if (!$content) {
                throw new Exception('Downloaded file is empty');
            }

            // Determine file extension from mime type
            $extension = match($file->mimeType) {
                'image/jpeg' => '.jpg',
                'image/png' => '.png',
                'image/gif' => '.gif',
                'image/webp' => '.webp',
                default => '.jpg'
            };

            // Generate filename if not provided
            if (!$customFileName) {
                $customFileName = 'drive_download_' . time();
            }
            $customFileName .= $extension;

            // Create target folder path
            $targetFolderId = $this->rootFolderId;
            if ($customPath) {
                $targetFolderId = $this->createNestedFolders($customPath, $this->rootFolderId);
            }

            // Create new file in our Drive
            $newFile = new DriveFile();
            $newFile->setName($customFileName);
            $newFile->setParents([$targetFolderId]);
            $newFile->setMimeType($file->mimeType);

            $created = $this->drive->files->create($newFile, [
                'data' => $content,
                'mimeType' => $file->mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,webViewLink,webContentLink',
                'supportsAllDrives' => true,
            ]);

            // Set public permission
            $permission = new Permission();
            $permission->setRole('reader');
            $permission->setType('anyone');
            $this->drive->permissions->create($created->id, $permission, [
                'supportsAllDrives' => true
            ]);

            Log::info('Successfully copied file from Google Drive link', [
                'source_link' => $driveLink,
                'source_file_id' => $fileId,
                'target_file_id' => $created->id,
                'filename' => $customFileName,
                'custom_path' => $customPath,
                'size' => $file->size
            ]);

            return [
                'id' => $created->id,
                'name' => $created->name,
                'url' => $created->webViewLink,
                'path' => $customPath ? $customPath . '/' . $customFileName : $customFileName,
                'size' => $file->size,
                'mime_type' => $file->mimeType
            ];

        } catch (Exception $e) {
            Log::error('Failed to copy from Google Drive link', [
                'link' => $driveLink,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Extract file ID from various Google Drive link formats
     */
    private function extractFileIdFromLink(string $link): ?string
    {
        // Handle different Google Drive link formats:
        // https://drive.google.com/file/d/FILE_ID/view
        // https://drive.google.com/open?id=FILE_ID
        // https://drive.google.com/uc?id=FILE_ID
        
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Create nested folders in Google Drive
     */
    private function createNestedFolders(string $path, string $parentId): string
    {
        $folders = explode('/', trim($path, '/'));
        $currentParentId = $parentId;

        foreach ($folders as $folderName) {
            if (empty($folderName)) continue;
            
            // Check if folder already exists
            $existingFolder = $this->findFolderByName($folderName, $currentParentId);
            
            if ($existingFolder) {
                $currentParentId = $existingFolder->id;
            } else {
                // Create new folder
                $folderMetadata = new DriveFile();
                $folderMetadata->setName($folderName);
                $folderMetadata->setParents([$currentParentId]);
                $folderMetadata->setMimeType('application/vnd.google-apps.folder');

                $folder = $this->drive->files->create($folderMetadata, [
                    'fields' => 'id',
                    'supportsAllDrives' => true,
                ]);
                
                $currentParentId = $folder->id;
            }
        }

        return $currentParentId;
    }

    /**
     * Find folder by name in specific parent
     */
    private function findFolderByName(string $name, string $parentId): ?DriveFile
    {
        try {
            $response = $this->drive->files->listFiles([
                'q' => "name='{$name}' and parents in '{$parentId}' and mimeType='application/vnd.google-apps.folder'",
                'fields' => 'files(id,name)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $response->getFiles();
            return count($files) > 0 ? $files[0] : null;
        } catch (Exception $e) {
            Log::error('Error finding folder by name', [
                'name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function copyFileToFolder(string $fileId, string $folderPath, ?string $newFileName = null): ?string
    {
        if (!$this->initialize()) {
            throw new Exception('Google Drive service not available: ' . $this->getError());
        }

        try {
            // Ensure the target folder exists
            $targetFolderId = $this->ensureNestedFolders($folderPath);
            
            // Get original file info
            $originalFile = $this->drive->files->get($fileId, [
                'fields' => 'id,name,mimeType',
                'supportsAllDrives' => true
            ]);

            // Prepare copy data
            $copyData = [
                'name' => $newFileName ?: ('Copy_of_' . $originalFile->getName()),
                'parents' => [$targetFolderId]
            ];

            // Create a copy of the file
            $driveFile = new \Google\Service\Drive\DriveFile($copyData);
            $copiedFile = $this->drive->files->copy($fileId, $driveFile, [
                'supportsAllDrives' => true,
                'fields' => 'id,name,webViewLink'
            ]);

            Log::info('File copied successfully', [
                'original_id' => $fileId,
                'copied_id' => $copiedFile->getId(),
                'folder_path' => $folderPath,
                'new_name' => $newFileName
            ]);

            return $copiedFile->getId();

        } catch (\Exception $e) {
            Log::error('Failed to copy file to folder', [
                'file_id' => $fileId,
                'folder_path' => $folderPath,
                'new_name' => $newFileName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reorganize jalur lowering files when date changes
     */
    public function reorganizeJalurLoweringFiles(int $loweringId, string $oldDate, string $newDate): array
    {
        try {
            Log::info('Starting jalur lowering folder reorganization', [
                'lowering_id' => $loweringId,
                'old_date' => $oldDate,
                'new_date' => $newDate
            ]);

            // Get lowering data with relationships
            $lowering = \App\Models\JalurLoweringData::with(['lineNumber.cluster'])->find($loweringId);
            if (!$lowering) {
                throw new \Exception("Lowering data not found: {$loweringId}");
            }

            $lineNumber = $lowering->lineNumber->line_number;
            $clusterName = $lowering->lineNumber->cluster->nama_cluster;

            // Build old and new folder paths
            $oldFolderPath = "JALUR_LOWERING/{$clusterName}/{$lineNumber}/{$oldDate}";
            $newFolderPath = "JALUR_LOWERING/{$clusterName}/{$lineNumber}/{$newDate}";

            Log::info('Folder paths for reorganization', [
                'old_path' => $oldFolderPath,
                'new_path' => $newFolderPath
            ]);

            // Get all photos for this lowering
            $photos = \App\Models\PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $loweringId)
                ->get();

            if ($photos->isEmpty()) {
                Log::info('No photos found for reorganization', ['lowering_id' => $loweringId]);
                return [
                    'success' => true,
                    'moved_count' => 0,
                    'message' => 'No photos to reorganize'
                ];
            }

            // Create new folder structure
            $newFolderId = $this->ensurePathExists($newFolderPath);

            $movedFiles = [];
            $errors = [];

            foreach ($photos as $photo) {
                try {
                    // Extract file ID from photo URL
                    $fileId = $this->extractFileIdFromUrl($photo->photo_url);
                    if (!$fileId) {
                        $errors[] = "Could not extract file ID from URL: {$photo->photo_url}";
                        continue;
                    }

                    // Move file to new folder
                    $result = $this->moveFileToFolder($fileId, $newFolderId);

                    // Update photo URL to reflect new path
                    $newUrl = str_replace($oldDate, $newDate, $photo->photo_url);
                    $photo->update(['photo_url' => $newUrl]);

                    $movedFiles[] = [
                        'photo_id' => $photo->id,
                        'field_name' => $photo->photo_field_name,
                        'old_url' => $photo->photo_url,
                        'new_url' => $newUrl
                    ];

                    Log::info('File moved successfully', [
                        'photo_id' => $photo->id,
                        'file_id' => $fileId,
                        'new_folder' => $newFolderPath
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Failed to move photo {$photo->id}: " . $e->getMessage();
                    Log::error('Failed to move individual file', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Try to remove old folder if empty
            try {
                $oldFolderId = $this->getFolderIdByPath($oldFolderPath);
                if ($oldFolderId) {
                    $this->deleteEmptyFolder($oldFolderId);
                }
            } catch (\Exception $e) {
                Log::warning('Could not clean up old folder', [
                    'old_path' => $oldFolderPath,
                    'error' => $e->getMessage()
                ]);
            }

            $result = [
                'success' => true,
                'moved_count' => count($movedFiles),
                'moved_files' => $movedFiles,
                'errors' => $errors,
                'old_path' => $oldFolderPath,
                'new_path' => $newFolderPath
            ];

            Log::info('Jalur lowering folder reorganization completed', $result);
            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to reorganize jalur lowering folder', [
                'lowering_id' => $loweringId,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Extract file ID from Google Drive URL
     */
    private function extractFileIdFromUrl(string $url): ?string
    {
        // Handle various Google Drive URL formats
        if (preg_match('/\/file\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/id=([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Ensure folder path exists, creating folders as needed
     */
    private function ensurePathExists(string $path): ?string
    {
        try {
            $pathParts = explode('/', trim($path, '/'));
            $currentFolderId = $this->rootFolderId;

            foreach ($pathParts as $folderName) {
                // Check if folder exists
                $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false";
                if ($currentFolderId) {
                    $query .= " and '{$currentFolderId}' in parents";
                }

                $results = $this->drive->files->listFiles(['q' => $query]);
                $folders = $results->getFiles();

                if (empty($folders)) {
                    // Create folder
                    $fileMetadata = new DriveFile([
                        'name' => $folderName,
                        'mimeType' => 'application/vnd.google-apps.folder',
                        'parents' => $currentFolderId ? [$currentFolderId] : []
                    ]);

                    $folder = $this->drive->files->create($fileMetadata, [
                        'fields' => 'id'
                    ]);

                    $currentFolderId = $folder->getId();
                } else {
                    $currentFolderId = $folders[0]->getId();
                }
            }

            return $currentFolderId;
        } catch (Exception $e) {
            Log::error('Failed to ensure path exists', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Move file to a specific folder
     */
    private function moveFileToFolder(string $fileId, string $newFolderId): bool
    {
        try {
            // Get current file metadata
            $file = $this->drive->files->get($fileId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->parents);

            // Move file to new folder
            $this->drive->files->update($fileId, new DriveFile(), [
                'addParents' => $newFolderId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents'
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to move file to folder', [
                'file_id' => $fileId,
                'new_folder_id' => $newFolderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete folder if empty
     */
    private function deleteEmptyFolder(string $folderId): bool
    {
        try {
            // Check if folder is empty
            $files = $this->drive->files->listFiles(['q' => "'{$folderId}' in parents and trashed=false"]);
            if ($files->getFiles()) {
                return false; // Folder not empty
            }

            // Delete empty folder
            $this->drive->files->delete($folderId);
            return true;
        } catch (\Exception $e) {
            Log::warning('Could not delete empty folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
