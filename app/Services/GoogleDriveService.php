<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
    private Drive $drive;
    private string $rootFolderId;

    public function __construct()
    {
        $client = new GoogleClient();
        $client->setApplicationName('AERGAS');
        $client->setScopes([Drive::DRIVE]); // full drive scope; ganti ke DRIVE_FILE jika ingin lebih ketat

        $saJson = (string) (config('services.google_drive.service_account_json') ?? '');
        $hasServiceAccount = $saJson && is_file($saJson);

        if ($hasServiceAccount) {
            // Service Account flow
            $client->setAuthConfig($saJson);
        } else {
            // OAuth + Refresh Token flow
            $client->setClientId((string) config('services.google_drive.client_id'));
            $client->setClientSecret((string) config('services.google_drive.client_secret'));

            $refresh = (string) (config('services.google_drive.refresh_token') ?? '');
            if ($refresh === '') {
                throw new Exception('Missing GOOGLE_DRIVE_REFRESH_TOKEN or service account json.');
            }

            // Dapatkan access token dari refresh token
            $token = $client->fetchAccessTokenWithRefreshToken($refresh);
            if (!empty($token['error'])) {
                throw new Exception('Failed to fetch access token: ' . ($token['error_description'] ?? $token['error']));
            }
            $client->setAccessToken($token);
        }

        $this->drive = new Drive($client);

        $this->rootFolderId = (string) (config('services.google_drive.folder_id') ?? '');
        if ($this->rootFolderId === '') {
            throw new Exception('GOOGLE_DRIVE_FOLDER_ID (services.google_drive.folder_id) is required.');
        }
    }

    /**
     * Pastikan seluruh path folder ada di bawah root, dan kembalikan folderId terakhir.
     * Contoh: ensureNestedFolders('aergas/SK/65765667__warung_makan_sederhana')
     */
    public function ensureNestedFolders(string $path): string
    {
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

    /**
     * Upload file langsung ke Drive ke dalam $folderId.
     * Return minimal: id, name, webViewLink, webContentLink
     */
    public function uploadFile(UploadedFile $file, string $folderId, ?string $targetName = null): array
    {
        $name = $targetName ?: $file->getClientOriginalName();
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $driveFile = new DriveFile([
            'name'    => $name,
            'parents' => [$folderId],
        ]);

        // Gunakan upload tipe multipart (metadata + konten)
        $created = $this->drive->files->create(
            $driveFile,
            [
                'data'       => file_get_contents($file->getRealPath()),
                'mimeType'   => $mime,
                'uploadType' => 'multipart',
                'fields'     => 'id,name,webViewLink,webContentLink',
                // 'supportsAllDrives' => true, // uncomment jika pakai Shared Drive
            ]
        );

        // Jadikan bisa diakses via tautan (anyone with the link)
        try {
            $perm = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->drive->permissions->create($created->id, $perm, [
                // 'supportsAllDrives' => true,
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
     * Mirror file dari disk/path lokal ke Drive di struktur: {root}/{module}/{reff}/{sub}/filename
     * Disediakan untuk kompatibilitas lama; untuk jalur baru sebaiknya pakai uploadFile().
     * Return: [fileId, webViewLink]
     */
    public function mirrorToDrive(string $disk, string $path, string $module, string $reffId, string $sub = 'original'): array
    {
        $abs = Storage::disk($disk)->path($path);
        if (!is_file($abs)) {
            throw new Exception("Local file not found: {$abs}");
        }

        // Struktur lama: <MODULE>_Data/<reff>/<sub>
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
        ]);

        // make it link-readable
        try {
            $perm = new Permission(['type' => 'anyone', 'role' => 'reader']);
            $this->drive->permissions->create($created->id, $perm);
        } catch (\Throwable $e) {
            Log::warning('Set permission failed (non fatal): ' . $e->getMessage());
        }

        return [$created->id, $created->webViewLink ?? null];
    }

    /** Hapus file di Drive (silent on failure) */
    public function deleteFile(string $fileId): void
    {
        try {
            $this->drive->files->delete($fileId, [
                // 'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('gdrive delete failed', ['id' => $fileId, 'err' => $e->getMessage()]);
        }
    }

    /** Cek koneksi + info akun/kuota (kalau ada) */
    public function testConnection(): array
    {
        try {
            $about = $this->drive->about->get(['fields' => 'user,storageQuota']);
            $user  = $about->getUser();
            $quota = $about->getStorageQuota();

            return [
                'success'       => true,
                'message'       => 'Connected to Google Drive',
                'user_email'    => $user?->getEmailAddress(),
                'storage_used'  => $quota?->getUsage(),
                'storage_limit' => $quota?->getLimit(),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoogleDrive testConnection failed: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** (opsional) Statistik sederhana */
    public function getStorageStats(): array
    {
        try {
            $about = $this->drive->about->get(['fields' => 'storageQuota']);
            $quota = $about->getStorageQuota();
            return [
                'used'        => (int) ($quota?->getUsage() ?? 0),
                'limit'       => $quota?->getLimit(),
                'used_human'  => $this->formatBytes((int) ($quota?->getUsage() ?? 0)),
                'folders'     => [],
            ];
        } catch (\Throwable $e) {
            Log::warning('GoogleDrive getStorageStats failed: '.$e->getMessage());
            return ['used' => 0, 'limit' => null, 'used_human' => '0 B', 'folders' => []];
        }
    }

    /* ======================= Helpers ======================= */

    /**
     * Pastikan ada folder anak bernama $name di bawah $parentId; kembalikan id-nya.
     */
    private function ensureFolder(string $parentId, string $name): string
    {
        // Hati-hati dengan karakter kutip
        $quoted = addcslashes($name, "'\\");
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$quoted}' and '{$parentId}' in parents and trashed=false";
        $list = $this->drive->files->listFiles([
            'q'      => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            // 'supportsAllDrives' => true,
            // 'includeItemsFromAllDrives' => true,
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
            // 'supportsAllDrives' => true,
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
