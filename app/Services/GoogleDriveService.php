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
}
