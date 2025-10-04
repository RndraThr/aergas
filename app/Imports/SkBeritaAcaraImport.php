<?php

namespace App\Imports;

use App\Models\SkData;
use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use App\Services\PhotoApprovalService;
use App\Services\GoogleDriveService;
use App\Services\FileUploadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkBeritaAcaraImport implements ToCollection, WithHeadingRow
{
    private bool $dryRun;
    private int $headingRow;
    private string $driveFolderLink;
    private PhotoApprovalService $photoApprovalService;
    private GoogleDriveService $driveService;
    private FileUploadService $fileUploadService;

    protected array $results = [
        'success' => 0,
        'skipped' => 0,
        'failed' => [],
        'details' => []
    ];

    public function __construct(
        string $driveFolderLink,
        bool $dryRun = false,
        int $headingRow = 1,
        ?PhotoApprovalService $photoApprovalService = null,
        ?GoogleDriveService $driveService = null,
        ?FileUploadService $fileUploadService = null
    ) {
        $this->driveFolderLink = trim($driveFolderLink);
        $this->dryRun = $dryRun;
        $this->headingRow = $headingRow;
        $this->photoApprovalService = $photoApprovalService ?? app(PhotoApprovalService::class);
        $this->driveService = $driveService ?? app(GoogleDriveService::class);
        $this->fileUploadService = $fileUploadService ?? app(FileUploadService::class);
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNo = $this->headingRow + 1 + $i;

            // Skip empty rows
            if (empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== ''))) {
                $this->results['skipped']++;
                continue;
            }

            try {
                $this->processRow($row->toArray(), $rowNo);
            } catch (\Throwable $e) {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => [$e->getMessage()],
                    'data' => $row->toArray()
                ];
                Log::error('SkBeritaAcaraImport row failed', [
                    'row' => $rowNo,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    private function processRow(array $row, int $rowNo): void
    {
        // Normalize keys
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeKey((string) $key);
            $normalized[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        // Extract data
        $reffId = $this->toString($normalized['reff_id'] ?? $normalized['reff id'] ?? $normalized['id reff'] ?? null);
        $namaBA = $this->toString($normalized['nama_ba'] ?? $normalized['nama ba'] ?? null);

        // Validate data
        $validator = Validator::make([
            'reff_id' => $reffId,
            'nama_ba' => $namaBA,
        ], [
            'reff_id' => ['required', 'string'],
            'nama_ba' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => $validator->errors()->all(),
                'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA]
            ];
            return;
        }

        // Check if customer exists
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        if (!$customer) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => ["Customer dengan reff_id {$reffId} tidak ditemukan"],
                'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA]
            ];
            return;
        }

        // Check if SK data exists, create if not exists
        $skData = SkData::where('reff_id_pelanggan', $reffId)->first();
        $skDataCreated = false;

        if (!$skData) {
            // Auto-create SK Data record for this customer if not exists
            // This allows importing BA for old data or data created outside the system
            try {
                // Create SK Data - following same pattern as SkDataController::store()
                // Note: nomor_sk is nullable and not used in normal flow
                $createData = [
                    'reff_id_pelanggan' => $reffId,
                    'tanggal_instalasi' => now(),
                    'status' => 'draft',
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1,
                    // Material fields will be null (can be filled later via SK Form)
                ];

                $skData = SkData::create($createData);
                $skDataCreated = true;

                Log::info('Auto-created SK Data for BA import', [
                    'reff_id' => $reffId,
                    'sk_id' => $skData->id
                ]);
            } catch (\Exception $e) {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => ["Gagal membuat SK Data untuk customer {$reffId}: " . $e->getMessage()],
                    'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA]
                ];
                return;
            }
        }

        // Check if BA already exists
        $existingBA = PhotoApproval::where('reff_id_pelanggan', $reffId)
            ->where('module_name', 'sk')
            ->where('photo_field_name', 'berita_acara')
            ->first();

        if ($existingBA) {
            $this->results['skipped']++;
            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'status' => 'skipped',
                'message' => 'Berita Acara sudah ada'
            ];
            return;
        }

        // Find file in Google Drive folder by searching for nama_ba
        $photoExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'JPG', 'JPEG', 'PNG', 'PDF'];
        $driveFileLink = null;

        // Try different filename variations
        foreach ($photoExtensions as $ext) {
            $testFileName = $namaBA . '.' . $ext;
            $testLink = $this->driveFolderLink . '/' . $testFileName;

            // Build potential Drive link
            $driveFileLink = $testLink;

            Log::info("Trying to find BA file in Drive", [
                'nama_ba' => $namaBA,
                'extension' => $ext,
                'folder_link' => $this->driveFolderLink
            ]);

            // We'll validate on upload, break on first extension
            break;
        }

        // Dry run mode - just validate
        if ($this->dryRun) {
            $this->results['success']++;

            $dryRunMessage = 'Data valid dan siap di-copy dari Drive (dry-run mode)';
            if (!$skData) {
                $dryRunMessage .= ' - SK Data akan dibuat otomatis';
            }

            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'drive_link' => $driveFileLink,
                'sk_data_exists' => $skData ? true : false,
                'status' => 'validated',
                'message' => $dryRunMessage
            ];
            return;
        }

        // Download file from Google Drive to temp, then upload using FileUploadService
        try {
            // Extract file ID from the Drive folder and search for file
            $sourceFolderId = $this->extractFolderIdFromLink($this->driveFolderLink);

            if (!$sourceFolderId) {
                throw new \Exception("Tidak dapat extract folder ID dari link: {$this->driveFolderLink}");
            }

            // Find file in folder by nama_ba
            $findResult = $this->findFileInFolder($sourceFolderId, $namaBA, $photoExtensions);

            if (!$findResult) {
                throw new \Exception("File dengan nama {$namaBA} tidak ditemukan di folder Drive");
            }

            [$sourceFileId, $originalFileName] = $findResult;

            // Download file from Google Drive to temp location
            $tempPath = $this->downloadFileFromDrive($sourceFileId, $originalFileName);

            if (!$tempPath || !file_exists($tempPath)) {
                throw new \Exception("Gagal download file dari Drive");
            }

            // Create UploadedFile from downloaded temp file
            $uploadedFile = new UploadedFile(
                $tempPath,
                $originalFileName,
                mime_content_type($tempPath),
                null,
                true // test mode = true (don't validate upload)
            );

            // Get customer name for folder naming
            $customer = $skData->calonPelanggan ?? CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
            $customerName = $customer ? $customer->nama_pelanggan : null;

            // Upload using FileUploadService (same as form upload)
            // This will create folder: aergas/SK/{reff_id}__{customer_slug}/
            $uploadResult = $this->fileUploadService->uploadPhoto(
                file: $uploadedFile,
                reffId: $reffId,
                module: 'SK',
                fieldName: 'berita_acara',
                uploadedBy: auth()->id() ?? 1,
                customerName: $customerName
            );

            // Clean up temp file
            @unlink($tempPath);

            if (!$uploadResult || empty($uploadResult['url'])) {
                throw new \Exception("Gagal upload file menggunakan FileUploadService");
            }

            // Create PhotoApproval record
            $photoApproval = PhotoApproval::create([
                'reff_id_pelanggan' => $reffId,
                'module_name' => 'sk',
                'photo_field_name' => 'berita_acara',
                'photo_url' => $uploadResult['url'],
                'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                'drive_link' => $uploadResult['drive_link'] ?? null,
                'storage_disk' => $uploadResult['disk'] ?? 'public',
                'storage_path' => $uploadResult['path'] ?? '',

                // AI validation - auto pass for BA
                'ai_status' => 'passed',
                'ai_score' => 1.0, // Decimal format (0-1)
                'ai_notes' => 'Berita Acara - imported from bulk upload, auto-passed',
                'ai_last_checked_at' => now(),

                // Photo status
                'photo_status' => 'tracer_pending',

                // Upload info
                'uploaded_by' => auth()->id() ?? 1,
                'uploaded_at' => now(),
            ]);

            // Sync module status
            if ($skData) {
                $skData->syncModuleStatusFromPhotos();
            }

            $this->results['success']++;

            $message = 'Berita Acara berhasil di-copy dari Drive folder';
            if ($skDataCreated) {
                $message .= ' (SK Data otomatis dibuat)';
            }

            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'photo_id' => $photoApproval->id,
                'drive_file_id' => $copiedFileId,
                'sk_data_created' => $skDataCreated,
                'status' => 'uploaded',
                'message' => $message
            ];

            Log::info('BA imported successfully from Drive', [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'photo_id' => $photoApproval->id,
                'source_file_id' => $sourceFileId,
                'copied_file_id' => $copiedFileId
            ]);

        } catch (\Throwable $e) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => ['Copy dari Drive gagal: ' . $e->getMessage()],
                'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA, 'drive_folder' => $this->driveFolderLink]
            ];

            Log::error('BA upload failed', [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }

    private function normalizeKey(string $key): string
    {
        $key = trim($key);
        $key = str_replace("\xC2\xA0", ' ', $key); // NBSP → space
        $key = preg_replace('/[\x00-\x1F\x7F\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $key);
        $key = str_replace(['_', '.', '/', '\\'], ' ', $key);
        $key = preg_replace('/\s+/u', ' ', $key);
        return mb_strtolower($key, 'UTF-8');
    }

    private function toString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');
        }
        return trim((string) $value);
    }

    /**
     * Extract folder ID from Google Drive link
     * Supports formats:
     * - https://drive.google.com/drive/folders/FOLDER_ID
     * - https://drive.google.com/drive/u/0/folders/FOLDER_ID
     */
    private function extractFolderIdFromLink(string $link): ?string
    {
        // Pattern: /folders/FOLDER_ID
        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }

        // Pattern: ?id=FOLDER_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }

        // If link is already just an ID (no special chars)
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $link)) {
            return $link;
        }

        return null;
    }

    /**
     * Find file in Google Drive folder by name
     * Try multiple extensions
     * @return array|null [fileId, fileName] or null if not found
     */
    private function findFileInFolder(string $folderId, string $fileName, array $extensions): ?array
    {
        try {
            // First, let's list ALL files in the folder to see what's there
            Log::info("Listing all files in Drive folder for debugging", [
                'folder_id' => $folderId,
                'searching_for' => $fileName
            ]);

            // Get all files in folder
            $allFiles = $this->listAllFilesInFolder($folderId);
            Log::info("All files found in Drive folder", [
                'folder_id' => $folderId,
                'total_files' => count($allFiles),
                'files' => $allFiles
            ]);

            // Try to find matching file
            foreach ($extensions as $ext) {
                $fullName = $fileName . '.' . $ext;

                Log::info("Searching for file in Drive folder", [
                    'folder_id' => $folderId,
                    'filename' => $fullName
                ]);

                // Use Drive API to list files in folder
                $fileId = $this->searchFileInDriveFolder($folderId, $fullName);

                if ($fileId) {
                    Log::info("File found in Drive", [
                        'filename' => $fullName,
                        'file_id' => $fileId
                    ]);
                    return [$fileId, $fullName]; // Return both fileId and filename
                }
            }

            // If not found, log what we were looking for vs what exists
            Log::warning("File not found after trying all extensions", [
                'folder_id' => $folderId,
                'searched_name' => $fileName,
                'tried_extensions' => $extensions,
                'available_files' => $allFiles
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Error finding file in Drive folder", [
                'folder_id' => $folderId,
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * List all files in a Google Drive folder (for debugging)
     * @return array Array of file names found in folder
     */
    private function listAllFilesInFolder(string $folderId): array
    {
        try {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);

            if (!$this->driveService->isAvailable()) {
                return [];
            }

            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                return [];
            }

            // List all files in folder
            $query = "'{$folderId}' in parents and trashed=false";

            $results = $drive->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name,mimeType)',
                'pageSize' => 100,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $results->getFiles();
            $fileList = [];

            foreach ($files as $file) {
                $fileList[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType()
                ];
            }

            return $fileList;

        } catch (\Exception $e) {
            Log::error("Error listing files in Drive folder", [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Search file in Drive folder using Google Drive API
     */
    private function searchFileInDriveFolder(string $folderId, string $fileName): ?string
    {
        try {
            // Use reflection to access private drive property from GoogleDriveService
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);

            // Initialize the service first
            if (!$this->driveService->isAvailable()) {
                throw new \Exception("Google Drive service not available");
            }

            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                throw new \Exception("Drive instance not initialized");
            }

            // Escape filename for query
            $escapedName = addslashes($fileName);

            // Build query to search in specific folder
            $query = "name='{$escapedName}' and '{$folderId}' in parents and trashed=false";

            $results = $drive->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name)',
                'pageSize' => 1,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $results->getFiles();

            if (!empty($files)) {
                return $files[0]->getId();
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Error searching file in Drive", [
                'folder_id' => $folderId,
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Download file from Google Drive to temp location
     * @return string|null Path to downloaded temp file
     */
    private function downloadFileFromDrive(string $fileId, string $fileName): ?string
    {
        try {
            // Use reflection to access private drive property from GoogleDriveService
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);

            $drive = $driveProperty->getValue($this->driveService);

            if (!$drive) {
                throw new \Exception("Drive instance not initialized");
            }

            // Download file content
            $response = $drive->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true
            ]);

            $content = $response->getBody()->getContents();

            if (empty($content)) {
                throw new \Exception("Downloaded file is empty");
            }

            // Create temp directory if not exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Save to temp file
            $tempPath = $tempDir . '/' . uniqid('ba_import_') . '_' . $fileName;
            file_put_contents($tempPath, $content);

            Log::info("File downloaded from Drive to temp", [
                'file_id' => $fileId,
                'filename' => $fileName,
                'temp_path' => $tempPath,
                'size' => strlen($content)
            ]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error("Error downloading file from Drive", [
                'file_id' => $fileId,
                'filename' => $fileName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}