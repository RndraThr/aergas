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
     * Upload foto ke disk default (disarankan 'public'), lalu (opsional) mirror ke Google Drive.
     * @return array{url:string,disk:string,path:string,drive_file_id:?string,drive_link:?string}
     * @throws Exception
     */
    public function uploadPhoto(
        UploadedFile $file,
        string $reffId,
        string $module,
        string $fieldName,
        int $uploadedBy
    ): array {
        $this->validateFile($file);

        $disk = config('filesystems.default', 'public');

        /** @var FilesystemAdapter $diskAdapter */
        $diskAdapter = Storage::disk($disk);

        $moduleSlug = Str::of($module)->snake()->lower();
        $dir = "AERGAS/{$moduleSlug}/{$reffId}/original";

        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = now()->format('Ymd_His') . "_{$baseName}.{$ext}";

        $path = $file->storeAs($dir, $filename, $disk);

        // INTELEPHENSE-SAFE: gunakan helper pembuat URL (punya fallback jika method url() tidak tersedia)
        $url = $this->makePublicUrl($diskAdapter, $disk, $path);

        // Optional: catat ke file_storages kalau tabelnya ada
        $fs = null;
        try {
            $fs = FileStorage::create([
                'reff_id_pelanggan' => $reffId,
                'module_name'       => $moduleSlug,
                'photo_field_name'  => $fieldName,
                'storage_disk'      => $disk,
                'path'              => $path,
                'url'               => $url,
                'mime_type'         => $file->getMimeType(),
                'size_bytes'        => $file->getSize(),
                'uploaded_by'       => $uploadedBy,
            ]);
        } catch (\Throwable $e) {
            Log::info('file_storages insert skipped (optional table)', ['err' => $e->getMessage()]);
        }

        // Mirror ke Google Drive jika dikonfigurasi
        $driveFileId = null;
        $driveLink = null;
        if ($this->googleDriveService && config('services.google_drive.folder_id')) {
            try {
                [$driveFileId, $driveLink] = $this->googleDriveService
                    ->mirrorToDrive($disk, $path, $moduleSlug, $reffId, 'original');

                if ($fs) {
                    $fs->update([
                        'drive_file_id'   => $driveFileId,
                        'drive_view_link' => $driveLink,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Mirror to Google Drive failed', ['err' => $e->getMessage()]);
            }
        }

        return [
            'url'           => $url,
            'disk'          => $disk,
            'path'          => $path,
            'drive_file_id' => $driveFileId,
            'drive_link'    => $driveLink,
        ];
    }

    /**
     * Hapus foto yang sudah ada (di storage & di Drive jika ada), plus rekaman file_storages.
     */
    public function deleteExistingPhoto(string $reffId, string $module, string $photoField): void
    {
        try {
            $moduleSlug = Str::of($module)->snake()->lower();

            $records = FileStorage::query()
                ->where('reff_id_pelanggan', $reffId)
                ->where('module_name', $moduleSlug)
                ->where('photo_field_name', $photoField)
                ->get();

            foreach ($records as $rec) {
                $recDisk = $rec->storage_disk ?: config('filesystems.default', 'public');

                /** @var FilesystemAdapter $diskAdapter */
                $diskAdapter = Storage::disk($recDisk);

                if ($rec->path && $diskAdapter->exists($rec->path)) {
                    $diskAdapter->delete($rec->path);
                }

                if (!empty($rec->drive_file_id) && $this->googleDriveService) {
                    try {
                        $this->googleDriveService->deleteFile($rec->drive_file_id);
                    } catch (\Throwable) {
                        // non fatal
                    }
                }

                $rec->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('deleteExistingPhoto soft-failed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Validasi ukuran & mime type berdasarkan config services.aergas.*
     * @throws Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        $maxBytes = (int) (config('services.photo_max_size') ?? (int) env('MAX_FILE_SIZE', 10240) * 1024);
        $allowedMimes = (array) (config('services.allowed_mime_types') ?? [
            'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'application/pdf'
        ]);

        if ($file->getSize() > $maxBytes) {
            throw new Exception('File terlalu besar. Maksimal ' . number_format($maxBytes / 1024 / 1024, 2) . ' MB');
        }

        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new Exception('Tipe file tidak diizinkan: ' . $file->getMimeType());
        }
    }

    /**
     * Bangun URL publik yang kompatibel dengan Intelephense & runtime.
     * - Jika adapter punya method url() → gunakan itu.
     * - Jika disk 'public' → /storage/{path} (butuh `php artisan storage:link`).
     * - Fallback: return path relatif (minimal tersaji ke UI meski tidak web-accessible).
     */
    private function makePublicUrl(FilesystemAdapter $diskAdapter, string $disk, string $path): string
    {
        // Larik runtime: FilesystemAdapter hampir selalu punya url(), tapi IDE tidak tahu.
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
            // symlink "public/storage" → "storage/app/public" wajib dibuat
            return "{$base}/storage/{$normalized}";
        }

        // Fallback terakhir (bukan URL publik, tapi tetap informatif)
        return $normalized;
    }
}
