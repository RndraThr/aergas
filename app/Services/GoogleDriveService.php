<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleDriveService
{
    private Drive $drive;
    private string $rootFolderId;

    public function __construct()
    {
        $client = new GoogleClient();
        $client->setApplicationName('AERGAS');

        $sa = config('services.google_drive.service_account_json');
        if ($sa && file_exists($sa)) {
            // Service account
            $client->setAuthConfig($sa);
            $client->useApplicationDefaultCredentials();
        } else {
            // OAuth refresh token flow
            $client->setClientId(config('services.google_drive.client_id'));
            $client->setClientSecret(config('services.google_drive.client_secret'));
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');

            $refresh = config('services.google_drive.refresh_token');
            if (!$refresh) {
                throw new Exception('GOOGLE_DRIVE_REFRESH_TOKEN is missing and no service account provided');
            }
            $client->refreshToken($refresh);
        }

        $client->setScopes([Drive::DRIVE]);
        $this->drive = new Drive($client);

        $this->rootFolderId = (string) config('services.google_drive.folder_id');
        if (!$this->rootFolderId) {
            throw new Exception('GOOGLE_DRIVE_FOLDER_ID (services.google_drive.folder_id) is required');
        }
    }

    /**
     * Mirror file dari disk/path ke Drive: AERGAS/{MODULE}_Data/{reffId}/{sub}/filename
     * Return: [fileId, webViewLink]
     */
    public function mirrorToDrive(string $disk, string $path, string $module, string $reffId, string $sub = 'original'): array
    {
        $abs = Storage::disk($disk)->path($path);
        if (!file_exists($abs)) {
            throw new Exception("Local file not found: {$abs}");
        }

        // Struktur folder
        $moduleFolderName = strtoupper($module) . '_Data';
        $folderModuleId = $this->ensureFolder($this->rootFolderId, $moduleFolderName);
        $folderReffId   = $this->ensureFolder($folderModuleId, $reffId);
        $folderSubId    = $this->ensureFolder($folderReffId, $sub);

        $file = new DriveFile([
            'name'    => basename($abs),
            'parents' => [$folderSubId],
        ]);

        $mime = mime_content_type($abs) ?: 'application/octet-stream';
        $created = $this->drive->files->create($file, [
            'data' => file_get_contents($abs),
            'mimeType' => $mime,
            'uploadType' => 'media',
            'fields' => 'id, webViewLink',
        ]);

        // make it accessible via link
        $perm = new Permission(['type' => 'anyone', 'role' => 'reader']);
        $this->drive->permissions->create($created->id, $perm);

        return [$created->id, $created->webViewLink ?? null];
    }

    public function deleteFile(string $fileId): void
    {
        try {
            $this->drive->files->delete($fileId);
        } catch (\Throwable $e) {
            Log::warning('gdrive delete failed', ['id' => $fileId, 'err' => $e->getMessage()]);
        }
    }

    public function testConnection(): array
    {
        try {
            // Implementasi asli bisa cek kredensial Google Client.
            // Untuk stub:
            $ok = (bool) config('services.google_drive.service_account_json');
            return [
                'success' => $ok,
                'message' => $ok ? 'Service account configured' : 'Service account not configured',
                // optional
                'user_email' => null,
                'storage_used' => null,
                'storage_limit'=> null,
            ];
        } catch (\Throwable $e) {
            Log::warning('GoogleDrive testConnection failed: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getStorageStats(): array
    {
        // Stub sederhana; ganti dengan panggilan Drive API jika siap
        return [
            'used'        => 0,
            'limit'       => null,
            'used_human'  => '0 B',
            'folders'     => [],
        ];
    }


    private function ensureFolder(string $parentId, string $name): string
    {
        $q = sprintf("mimeType='application/vnd.google-apps.folder' and name='%s' and '%s' in parents and trashed=false",
            addslashes($name), $parentId
        );
        $list = $this->drive->files->listFiles(['q' => $q, 'fields' => 'files(id,name)', 'pageSize' => 1]);
        if (count($list->getFiles()) > 0) {
            return $list->getFiles()[0]->getId();
        }

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);
        $created = $this->drive->files->create($folder, ['fields' => 'id']);
        return $created->id;
    }
}
