<?php

namespace App\Services;

use App\Models\FileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    public function __construct(
        private ?GoogleDriveService $googleDriveService = null
    ) {}

    /**
     * Upload foto ke Google Drive (prioritas). Jika Drive tidak tersedia/konfigurasi kosong → fallback ke disk lokal.
     *
     * Folder: aergas/{MODULE}/{reff}__{slug-nama}/
     *
     * @param  UploadedFile  $file
     * @param  string        $reffId
     * @param  string        $module       'SK' | 'SR'
     * @param  string        $fieldName    nama slot/field di modul
     * @param  int           $uploadedBy   user id
     * @param  string|null   $customerName untuk penamaan folder {reff}__{slug-nama}
     * @param  array         $options      ['target_name' => 'custom_name.ext']  // opsional
     * @return array{
     *   url:string,
     *   disk:string,          // 'gdrive' | 'public' (fallback)
     *   path:string,          // path pseudo (gdrive) atau path lokal
     *   drive_file_id:?string,
     *   drive_link:?string,
     *   file_storage_id:?int,
     *   filename:string,
     *   mime:string,
     *   bytes:int
     * }
     * @throws \Exception
     */
    public function uploadPhoto(
        UploadedFile $file,
        string $reffId,
        string $module,        // 'SK' | 'SR'
        string $fieldName,     // slot/field di modul
        int $uploadedBy,
        ?string $customerName = null,
        array $options = []    // 👈 dukung target_name
    ): array {
        $this->validateFile($file);

        $moduleLower = strtolower($module);      // 'sk' | 'sr' untuk DB
        $folderPath  = $this->buildFolder($module, $reffId, $customerName);

        // Deteksi ekstensi yang aman
        $ext = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
            ?: 'jpg'
        );

        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
        $slotSlug = Str::slug($fieldName, '_');
        $ts       = now()->format('Ymd_His');

        // 🔧 PRIORITAS 1: pakai nama yang dipaksa caller (controller/service)
        if (!empty($options['target_name'])) {
            $targetName = (string) $options['target_name'];
            // fallback: kalau caller lupa kasih ekstensi
            if (!Str::of($targetName)->contains('.')) {
                $targetName .= ".{$ext}";
            }
        } else {
            // 🔧 PRIORITAS 2: pola default dari config (opsional), else fallback standar
            // config('aergas_photos.naming.pattern'): 'reff_slot_ts' | 'reff_slot' | 'ts_reff_slot_orig'
            $pattern = (string) data_get(config('aergas_photos'), 'naming.pattern', 'reff_slot_ts');
            switch ($pattern) {
                case 'reff_slot':            // tanpa timestamp (rawan tabrakan kalau tidak hapus yang lama)
                    $targetName = "{$reffId}_{$slotSlug}.{$ext}";
                    break;
                case 'ts_reff_slot_orig':    // lengkap (timestamp + reff + slot + nama asli)
                    $targetName = "{$ts}_{$reffId}_{$slotSlug}_{$baseName}.{$ext}";
                    break;
                case 'reff_slot_ts':         // ✅ REKOMENDASI (unik & ringkas)
                default:
                    $targetName = "{$reffId}_{$slotSlug}_{$ts}.{$ext}";
                    break;
            }
        }

        // Variabel hasil
        $usedDisk  = 'public';
        $finalPath = $folderPath . '/' . $targetName;
        $driveId   = null;
        $driveLink = null;
        $publicUrl = '';
        $bytes     = (int) $file->getSize();
        $mime      = (string) $file->getMimeType();

        // ===== 1) Coba upload langsung ke Google Drive =====
        if ($this->canUseDrive()) {
            try {
                $folderId = $this->googleDriveService->ensureNestedFolders($folderPath);
                $u        = $this->googleDriveService->uploadFile($file, $folderId, $targetName);

                $driveId   = $u['id'] ?? null;
                $driveLink = $u['webViewLink'] ?? ($u['webContentLink'] ?? null);

                $usedDisk  = 'gdrive';
                $publicUrl = $driveLink ?? '';
                // Simpan path pseudo agar tetap ada informasi struktur
                $finalPath = $folderPath . '/' . ($u['name'] ?? $targetName);
            } catch (\Throwable $e) {
                Log::warning('Upload to Google Drive failed; falling back to local', ['err' => $e->getMessage()]);
            }
        }

        // ===== 2) Fallback ke disk lokal (public) =====
        if ($usedDisk !== 'gdrive') {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $diskAdapter */
            $diskAdapter = Storage::disk('public');
            $stored = $file->storeAs($folderPath, $targetName, 'public');
            $finalPath = $stored;
            $publicUrl = $this->makePublicUrl($diskAdapter, 'public', $stored);
        }

        // ===== 3) Catat ke file_storages (jika tabel/model tersedia) =====
        $fsId = null;
        try {
            $payload = [
                'reff_id_pelanggan' => $reffId,
                'module_name'       => $moduleLower,
                'photo_field_name'  => $fieldName,
                'original_filename' => $file->getClientOriginalName(), // ✅ ADD THIS
                'stored_filename'   => $targetName,                    // ✅ ADD THIS TOO
                'file_path'         => $finalPath,                     // ✅ ADD THIS TOO
                'storage_disk'      => $usedDisk,
                'path'              => $finalPath,
                'url'               => $publicUrl,
                'file_size'         => $bytes,
                'mime_type'         => $mime,
                'size_bytes'        => $bytes,
                'uploaded_by'       => $uploadedBy,
                'status'            => 'active',
            ];

            // kolom drive opsional
            if ($driveId)   $payload['drive_file_id']   = $driveId;
            if ($driveLink) $payload['drive_view_link'] = $driveLink;

            /** @var \App\Models\FileStorage $fs */
            $fs   = FileStorage::create($payload);
            $fsId = $fs->id ?? null;
        } catch (\Throwable $e) {
            Log::info('file_storages insert skipped', ['err' => $e->getMessage()]);
        }

        return [
            'url'             => $publicUrl,
            'disk'            => $usedDisk,
            'path'            => $finalPath,
            'drive_file_id'   => $driveId,
            'drive_link'      => $driveLink,
            'file_storage_id' => $fsId,
            'filename'        => $targetName,
            'mime'            => $mime,
            'bytes'           => $bytes,
        ];
    }


    /**
     * Hapus semua file untuk kombinasi (reff, module, field).
     * Menghapus di Drive kalau ada, dan file lokal bila fallback pernah dibuat.
     */
    public function deleteExistingPhoto(string $reffId, string $module, string $photoField): void
    {
        try {
            $moduleLower = strtolower($module);

            $records = FileStorage::query()
                ->where('reff_id_pelanggan', $reffId)
                ->where('module_name', $moduleLower)
                ->where('photo_field_name', $photoField)
                ->get();

            foreach ($records as $rec) {
                $recDisk = $rec->storage_disk ?: 'public';

                // Hapus dari Google Drive
                if (($recDisk === 'gdrive' || !empty($rec->drive_file_id)) && $this->googleDriveService) {
                    try {
                        if (!empty($rec->drive_file_id)) {
                            $this->googleDriveService->deleteFile($rec->drive_file_id);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('delete drive file failed (non fatal)', ['err' => $e->getMessage()]);
                    }
                }

                // Hapus file lokal bila ada
                if ($rec->path && $recDisk !== 'gdrive') {
                    try {
                        $diskAdapter = Storage::disk($recDisk);
                        if ($diskAdapter->exists($rec->path)) {
                            $diskAdapter->delete($rec->path);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('delete local file failed (non fatal)', ['err' => $e->getMessage()]);
                    }
                }

                // Hapus record
                try { $rec->delete(); } catch (\Throwable) {}
            }
        } catch (\Throwable $e) {
            Log::warning('deleteExistingPhoto soft-failed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Validasi ukuran & mime type (env/config).
     * @throws Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Ambil dari aergas_photos.limits dulu; fallback ke services; terakhir ke ENV/default
        $cfgAergas = config('aergas_photos');   // pasti array/null
        $cfgSvc    = config('services');        // pasti array/null

        $maxBytes = (int) (
            data_get($cfgAergas, 'limits.max_bytes') ?:
            data_get($cfgSvc,   'photo_max_size_bytes') ?:
            // ENV fallback: PHOTO_MAX_SIZE_BYTES (langsung bytes) atau MAX_FILE_SIZE (KB)
            env('PHOTO_MAX_SIZE_BYTES', env('MAX_FILE_SIZE', 10240) * 1024)
        );

        $allowedMimes = (array) (
            data_get($cfgAergas, 'limits.allowed_mime_types') ?:
            data_get($cfgSvc,   'allowed_mime_types') ?:
            // Default aman
            ['image/jpeg','image/png','image/jpg','image/webp','application/pdf']
        );

        if ($file->getSize() > $maxBytes) {
            throw new Exception('File terlalu besar. Maksimal ' . number_format($maxBytes / 1024 / 1024, 2) . ' MB');
        }
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new Exception('Tipe file tidak diizinkan: ' . $file->getMimeType());
        }
    }


    /** Pakai Drive kalau service terpasang & folder root diset */
    private function canUseDrive(): bool
    {
        if (!$this->googleDriveService) return false;
        $root = (string) (config('services.google_drive.folder_id') ?? '');
        return $root !== '';
    }

    /** aergas/{MODULE}/{reff}__{slug-nama} */
    private function buildFolder(string $module, string $reffId, ?string $customerName = null): string
    {
        $module = strtoupper($module); // SK / SR
        $slug   = $customerName ? Str::slug($customerName, '_') : null;
        $leaf   = $slug ? "{$reffId}__{$slug}" : $reffId;
        return "aergas/{$module}/{$leaf}";
    }

    /**
     * Buat URL publik untuk file lokal.
     * - Jika adapter punya method url() → gunakan
     * - Jika disk 'public' → /storage/{path}
     * - Fallback: path relatif
     */
    private function makePublicUrl(FilesystemAdapter $diskAdapter, string $disk, string $path): string
    {
        if (method_exists($diskAdapter, 'url')) {
            try {
                return $diskAdapter->url($path);
            } catch (\Throwable $e) {
                Log::warning('disk->url() failed, falling back', ['err' => $e->getMessage()]);
            }
        }

        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        if ($disk === 'public') {
            $base = rtrim((string) config('app.url'), '/');
            return "{$base}/storage/{$normalized}";
        }
        return $normalized;
    }
}
