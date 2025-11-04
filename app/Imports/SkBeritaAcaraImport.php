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
    private string $evidenceType; // ✨ NEW: Evidence type selection
    private bool $forceUpdate; // ✨ NEW: Force update flag
    private int $userId; // ✨ NEW: User ID (stored before session close)
    private PhotoApprovalService $photoApprovalService;
    private GoogleDriveService $driveService;
    private FileUploadService $fileUploadService;

    // ✨ OPTIMIZATION 1: Cache Drive folder file list (avoid repeated API calls)
    private ?array $cachedDriveFiles = null;
    private ?string $cachedFolderId = null;

    // ✨ OPTIMIZATION 3: Progress tracking
    private int $totalRows = 0;
    private int $processedRows = 0;
    private float $startTime;

    protected array $results = [
        'success' => 0,
        'skipped' => 0,
        'failed' => [],
        'details' => [],
        'performance' => [
            'total_time' => 0,
            'avg_time_per_row' => 0,
            'cache_hits' => 0,
            'api_calls_saved' => 0
        ]
    ];

    public function __construct(
        string $driveFolderLink,
        bool $dryRun = false,
        int $headingRow = 1,
        string $evidenceType = 'berita_acara',
        bool $forceUpdate = false,
        ?int $userId = null, // ✨ NEW: User ID (because session might be closed)
        ?PhotoApprovalService $photoApprovalService = null,
        ?GoogleDriveService $driveService = null,
        ?FileUploadService $fileUploadService = null
    ) {
        $this->driveFolderLink = trim($driveFolderLink);
        $this->dryRun = $dryRun;
        $this->headingRow = $headingRow;
        $this->evidenceType = $evidenceType;
        $this->forceUpdate = $forceUpdate;
        $this->userId = $userId ?? (auth()->check() ? auth()->id() : 1); // Try auth, fallback to 1
        $this->photoApprovalService = $photoApprovalService ?? app(PhotoApprovalService::class);
        $this->driveService = $driveService ?? app(GoogleDriveService::class);
        $this->fileUploadService = $fileUploadService ?? app(FileUploadService::class);
        $this->startTime = microtime(true); // ✨ Start performance timer
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        // ✨ OPTIMIZATION 3: Initialize progress tracking
        $this->totalRows = $rows->count();
        $nonEmptyRows = $rows->filter(fn($row) => !empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== '')));

        Log::info("Starting bulk import", [
            'total_rows' => $this->totalRows,
            'non_empty_rows' => $nonEmptyRows->count(),
            'mode' => $this->dryRun ? 'dry-run' : 'commit'
        ]);

        foreach ($rows as $i => $row) {
            $rowNo = $this->headingRow + 1 + $i;
            $this->processedRows++;

            // Skip empty rows
            if (empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== ''))) {
                $this->results['skipped']++;
                continue;
            }

            // ✨ OPTIMIZATION 3: Log progress every 10 rows
            if ($this->processedRows % 10 === 0) {
                $progress = round(($this->processedRows / $this->totalRows) * 100, 1);
                $elapsed = round(microtime(true) - $this->startTime, 2);
                $avgTime = $this->processedRows > 0 ? round($elapsed / $this->processedRows, 2) : 0;
                $remaining = $this->totalRows - $this->processedRows;
                $eta = round($remaining * $avgTime);

                Log::info("Bulk import progress", [
                    'processed' => $this->processedRows,
                    'total' => $this->totalRows,
                    'progress' => "{$progress}%",
                    'elapsed' => "{$elapsed}s",
                    'avg_per_row' => "{$avgTime}s",
                    'eta' => "{$eta}s",
                    'success' => $this->results['success'],
                    'skipped' => $this->results['skipped'],
                    'failed' => count($this->results['failed'])
                ]);
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

        // ✨ OPTIMIZATION 3: Final performance summary
        $totalTime = microtime(true) - $this->startTime;
        $this->results['performance']['total_time'] = round($totalTime, 2);
        $this->results['performance']['avg_time_per_row'] = $this->processedRows > 0
            ? round($totalTime / $this->processedRows, 2)
            : 0;

        Log::info("Bulk import completed", [
            'total_rows' => $this->totalRows,
            'processed' => $this->processedRows,
            'success' => $this->results['success'],
            'skipped' => $this->results['skipped'],
            'failed' => count($this->results['failed']),
            'total_time' => "{$this->results['performance']['total_time']}s",
            'avg_time_per_row' => "{$this->results['performance']['avg_time_per_row']}s",
            'cache_hits' => $this->results['performance']['cache_hits'],
            'api_calls_saved' => $this->results['performance']['api_calls_saved']
        ]);
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

        // Debug: Log the extracted values to see exact format
        Log::debug("Extracted row data", [
            'row' => $rowNo,
            'reff_id_raw' => $normalized['reff_id'] ?? $normalized['reff id'] ?? $normalized['id reff'] ?? null,
            'nama_ba_raw' => $normalized['nama_ba'] ?? $normalized['nama ba'] ?? null,
            'reff_id_processed' => $reffId,
            'nama_ba_processed' => $namaBA,
            'nama_ba_length' => strlen($namaBA ?? ''),
            'nama_ba_contains_spaces' => strpos($namaBA ?? '', ' ') !== false
        ]);

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
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
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

        // ✨ NEW: Check if evidence already exists (using dynamic evidence type)
        $existingEvidence = PhotoApproval::where('reff_id_pelanggan', $reffId)
            ->where('module_name', 'sk')
            ->where('photo_field_name', $this->evidenceType)
            ->first();

        if ($existingEvidence && !$this->forceUpdate) {
            // Evidence exists and force update is disabled - skip
            $this->results['skipped']++;
            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'status' => 'skipped',
                'message' => "Evidence {$this->evidenceType} sudah ada (gunakan Force Update untuk menimpa)"
            ];
            return;
        }

        // ✨ NEW: If force update enabled and evidence exists, prepare for replacement
        $isReplacement = $existingEvidence && $this->forceUpdate;

        // ✨ NEW: Dry run mode - validate file exists in Drive first
        if ($this->dryRun) {
            // Check if file actually exists in Google Drive
            $photoExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'JPG', 'JPEG', 'PNG', 'PDF'];

            // Extract folder ID and search for file
            $sourceFolderId = $this->extractFolderIdFromLink($this->driveFolderLink);

            if (!$sourceFolderId) {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => ["Tidak dapat extract folder ID dari link: {$this->driveFolderLink}"],
                    'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA]
                ];
                return;
            }

            // Try to find file in Drive folder
            $findResult = $this->findFileInFolder($sourceFolderId, $namaBA, $photoExtensions);

            // ✨ DEBUG: Log search attempt for troubleshooting
            Log::info("Dry-run file search", [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'nama_ba_hex' => bin2hex($namaBA), // Show hex to detect hidden chars
                'search_result' => $findResult ? 'FOUND' : 'NOT_FOUND',
                'actual_filename' => $findResult ? $findResult[1] : null
            ]);

            if ($findResult) {
                // ✅ File FOUND in Drive
                [$fileId, $actualFileName] = $findResult;

                $this->results['success']++;

                $dryRunMessage = '✅ File ditemukan di Drive';
                if (!$skData) {
                    $dryRunMessage .= ' - SK Data akan dibuat otomatis';
                }
                if ($isReplacement) {
                    $dryRunMessage .= ' - AKAN MENIMPA evidence yang ada (Force Update)';
                }

                $this->results['details'][] = [
                    'row' => $rowNo,
                    'reff_id' => $reffId,
                    'nama_ba' => $namaBA,
                    'file_found' => true,
                    'drive_filename' => $actualFileName,
                    'drive_file_id' => $fileId,
                    'sk_data_exists' => $skData ? true : false,
                    'will_replace' => $isReplacement,
                    'status' => 'validated',
                    'message' => $dryRunMessage
                ];
            } else {
                // ❌ File NOT FOUND in Drive
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => ["❌ File dengan nama {$namaBA} tidak ditemukan di folder Drive"],
                    'data' => ['reff_id' => $reffId, 'nama_ba' => $namaBA, 'drive_folder' => $this->driveFolderLink]
                ];

                Log::warning('Dry-run: File not found in Drive', [
                    'row' => $rowNo,
                    'reff_id' => $reffId,
                    'nama_ba' => $namaBA,
                    'folder_id' => $sourceFolderId
                ]);
            }

            return;
        }

        // Find file in Google Drive folder by searching for nama_ba (for commit mode)
        $photoExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'JPG', 'JPEG', 'PNG', 'PDF'];

        // ✨ OPTIMIZED: Use direct copy within Google Drive (NO download/upload!)
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

            // Get customer name for folder naming
            $customer = $skData->calonPelanggan ?? CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
            $customerName = $customer ? $customer->nama_pelanggan : null;

            // ✨ NEW: If force update, delete old file from storage first
            if ($isReplacement && $existingEvidence) {
                try {
                    // Use FileUploadService to handle deletion (supports Google Drive)
                    $this->fileUploadService->deleteExistingPhoto($reffId, 'SK', $this->evidenceType);

                    Log::info('Force update: Deleted old evidence', [
                        'reff_id' => $reffId,
                        'evidence_type' => $this->evidenceType,
                        'old_photo_id' => $existingEvidence->id
                    ]);
                } catch (\Throwable $e) {
                    // Non-fatal: Continue with upload even if delete fails
                    Log::warning('Force update: Failed to delete old file (continuing anyway)', [
                        'reff_id' => $reffId,
                        'evidence_type' => $this->evidenceType,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ✨ DIRECT COPY OPTIMIZATION: Copy within Drive (NO download/upload!)
            Log::info("Starting DIRECT COPY from Drive to Drive", [
                'reff_id' => $reffId,
                'source_file_id' => $sourceFileId,
                'source_filename' => $originalFileName,
                'evidence_type' => $this->evidenceType
            ]);

            $copyStartTime = microtime(true);

            // Build target folder path (same structure as FileUploadService)
            $module = 'SK';
            $folderPath = $this->buildFolderPath($module, $reffId, $customerName);

            // Ensure target folder exists in Google Drive
            $targetFolderId = $this->driveService->ensureNestedFolders($folderPath);

            // Generate target filename (same pattern as FileUploadService)
            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $slotSlug = \Illuminate\Support\Str::slug($this->evidenceType, '_');
            $ts = now()->format('Ymd_His');
            $targetFileName = "{$reffId}_{$slotSlug}_{$ts}.{$ext}";

            // ✨ USE DIRECT COPY API (NO download, NO upload!)
            $copyResult = $this->driveService->copyFileDirect(
                sourceFileId: $sourceFileId,
                targetFolderId: $targetFolderId,
                newFileName: $targetFileName
            );

            $copyTime = microtime(true) - $copyStartTime;

            Log::info("DIRECT COPY completed", [
                'reff_id' => $reffId,
                'new_file_id' => $copyResult['id'],
                'new_filename' => $copyResult['name'],
                'copy_time' => round($copyTime, 2) . 's',
                'speed_improvement' => 'Skipped download+upload!'
            ]);

            // Build uploadResult format (compatible with existing code)
            $uploadResult = [
                'url' => $copyResult['webViewLink'] ?? $copyResult['webContentLink'] ?? '',
                'disk' => 'gdrive',
                'drive_file_id' => $copyResult['id'],
                'file_path' => $folderPath . '/' . $copyResult['name'],
                'file_name' => $copyResult['name'],
                'mime_type' => $copyResult['mimeType'] ?? 'image/jpeg',
                'file_size' => $copyResult['size'] ?? 0
            ];

            Log::info("Direct copy result prepared", [
                'reff_id' => $reffId,
                'upload_result_keys' => array_keys($uploadResult)
            ]);

            // No temp file to clean up! (Direct copy = no download)

            if (!$uploadResult || empty($uploadResult['url'])) {
                throw new \Exception("Gagal copy file menggunakan Google Drive direct copy");
            }

            // ✨ NEW: Update or Create PhotoApproval record
            if ($isReplacement && $existingEvidence) {
                // Force update: Update existing record and reset status
                $updateData = [
                    'photo_url' => $uploadResult['url'],
                    'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                    'drive_link' => $uploadResult['url'] ?? null, // Same as photo_url for direct copy
                    'storage_disk' => $uploadResult['disk'] ?? 'gdrive',
                    'storage_path' => $uploadResult['file_path'] ?? '',

                    // AI validation - auto pass
                    'ai_status' => 'passed',
                    'ai_score' => 1.0,
                    'ai_notes' => "Evidence {$this->evidenceType} - Force updated from bulk import, auto-passed",
                    'ai_last_checked_at' => now(),

                    // ✅ RESET status to tracer_pending
                    'photo_status' => 'tracer_pending',

                    // Upload info
                    'uploaded_by' => $this->userId,
                    'uploaded_at' => now(),
                ];

                // ✨ Reset approval fields only if columns exist
                // Check what approval fields exist in the table
                $tableColumns = \Schema::getColumnListing('photo_approvals');

                $approvalFields = [
                    'tracer_approved_by' => null,
                    'tracer_approved_at' => null,
                    'tracer_rejected_at' => null,
                    'supervisor_approved_by' => null,
                    'supervisor_approved_at' => null,
                    'cgp_approved_at' => null,
                    'cgp_approved_by' => null,
                    'cgp_rejected_at' => null,
                    'reverted_at' => null,
                    'revision_notes' => null,
                ];

                foreach ($approvalFields as $field => $value) {
                    if (in_array($field, $tableColumns)) {
                        $updateData[$field] = $value;
                    }
                }

                $existingEvidence->update($updateData);
                $photoApproval = $existingEvidence;

                Log::info('Force update: Evidence replaced and status reset to tracer_pending', [
                    'photo_id' => $photoApproval->id,
                    'reff_id' => $reffId,
                    'evidence_type' => $this->evidenceType,
                    'reset_fields' => array_keys(array_filter($updateData, fn($v) => $v === null))
                ]);
            } else {
                // Create new PhotoApproval record
                $photoApproval = PhotoApproval::create([
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => 'sk',
                    'photo_field_name' => $this->evidenceType, // ✨ NEW: Use dynamic evidence type
                    'photo_url' => $uploadResult['url'],
                    'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                    'drive_link' => $uploadResult['url'] ?? null, // Same as photo_url for direct copy
                    'storage_disk' => $uploadResult['disk'] ?? 'gdrive',
                    'storage_path' => $uploadResult['file_path'] ?? '',

                    // AI validation - auto pass
                    'ai_status' => 'passed',
                    'ai_score' => 1.0,
                    'ai_notes' => "Evidence {$this->evidenceType} - imported from bulk upload, auto-passed",
                    'ai_last_checked_at' => now(),

                    // Photo status
                    'photo_status' => 'tracer_pending',

                    // Upload info
                    'uploaded_by' => $this->userId,
                    'uploaded_at' => now(),
                ]);
            }

            // ✨ NEW: Create FileStorage record (for tracking and audit trail)
            try {
                \App\Models\FileStorage::create([
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => 'sk',
                    'field_name' => $this->evidenceType,
                    'original_filename' => $originalFileName,
                    'stored_filename' => $uploadResult['file_name'] ?? $targetFileName,
                    'file_path' => $uploadResult['file_path'],
                    'mime_type' => $uploadResult['mime_type'] ?? 'image/jpeg',
                    'file_size' => $uploadResult['file_size'] ?? 0,
                    'file_hash' => null, // Not available for direct copy (no local file)
                    'google_drive_id' => $uploadResult['drive_file_id'],
                    'uploaded_by' => $this->userId,
                ]);
            } catch (\Throwable $e) {
                // Non-fatal: Continue even if FileStorage creation fails
                Log::warning('FileStorage record creation failed (non-fatal)', [
                    'reff_id' => $reffId,
                    'error' => $e->getMessage()
                ]);
            }

            // Sync module status
            if ($skData) {
                $skData->syncModuleStatusFromPhotos();
            }

            $this->results['success']++;

            // ✨ NEW: Dynamic message based on evidence type and force update
            $message = "Evidence {$this->evidenceType} berhasil di-copy dari Drive folder";
            if ($isReplacement) {
                $message .= ' (FORCE UPDATE - Status di-reset ke Tracer Pending)';
            }
            if ($skDataCreated) {
                $message .= ' (SK Data otomatis dibuat)';
            }

            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_ba' => $namaBA,
                'photo_id' => $photoApproval->id,
                'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                'sk_data_created' => $skDataCreated,
                'evidence_type' => $this->evidenceType,
                'force_updated' => $isReplacement,
                'status' => $isReplacement ? 'force_updated' : 'uploaded',
                'message' => $message
            ];

            Log::info('Evidence imported successfully from Drive', [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'photo_id' => $photoApproval->id,
                'evidence_type' => $this->evidenceType,
                'source_file_id' => $sourceFileId,
                'uploaded_drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                'used_disk' => $uploadResult['disk'] ?? null
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
     * ✨ OPTIMIZATION 1 & 2: Use cached file list, remove redundant API calls
     * @return array|null [fileId, fileName] or null if not found
     */
    private function findFileInFolder(string $folderId, string $fileName, array $extensions): ?array
    {
        try {
            // ✨ OPTIMIZATION 1: Check if we need to fetch file list from cache or API
            if ($this->cachedDriveFiles === null || $this->cachedFolderId !== $folderId) {
                // First time or different folder - fetch from API
                Log::info("Fetching Drive folder file list (cache MISS)", [
                    'folder_id' => $folderId,
                    'cache_was_null' => $this->cachedDriveFiles === null,
                    'folder_changed' => $this->cachedFolderId !== $folderId
                ]);

                $this->cachedDriveFiles = $this->listAllFilesInFolder($folderId);
                $this->cachedFolderId = $folderId;

                Log::info("Drive folder file list cached", [
                    'folder_id' => $folderId,
                    'total_files' => count($this->cachedDriveFiles)
                ]);
            } else {
                // Using cached list
                $this->results['performance']['cache_hits']++;
                $this->results['performance']['api_calls_saved']++;

                Log::debug("Using cached Drive folder file list (cache HIT)", [
                    'folder_id' => $folderId,
                    'cache_hits' => $this->results['performance']['cache_hits']
                ]);
            }

            // ✨ OPTIMIZATION 2: Search in cached list (NO API call needed!)
            // This replaces the searchFileInDriveFolder() call
            foreach ($extensions as $ext) {
                $fullName = $fileName . '.' . $ext;

                // ✨ DEBUG: Log each search attempt
                Log::debug("Searching for file with extension", [
                    'searching_for' => $fullName,
                    'searching_for_hex' => bin2hex($fullName),
                    'extension' => $ext
                ]);

                // Search in cached file list (case-insensitive comparison)
                foreach ($this->cachedDriveFiles as $file) {
                    // ✨ DEBUG: Log comparison for files that start with same base
                    if (stripos($file['name'], $fileName) === 0) {
                        Log::debug("Comparing similar file", [
                            'searching_for' => $fullName,
                            'found_file' => $file['name'],
                            'found_file_hex' => bin2hex($file['name']),
                            'strcasecmp_result' => strcasecmp($file['name'], $fullName)
                        ]);
                    }

                    if (strcasecmp($file['name'], $fullName) === 0) {
                        Log::info("File found in cached list", [
                            'filename' => $fullName,
                            'actual_filename' => $file['name'],
                            'file_id' => $file['id'],
                            'cache_hit' => true
                        ]);
                        return [$file['id'], $file['name']]; // Return actual filename from Drive
                    }
                }
            }

            // If not found, log what we were looking for vs what exists
            // Also check if there's a similar filename (for debugging)
            $allFileNames = array_column($this->cachedDriveFiles, 'name');
            $similarFiles = array_filter($allFileNames, function($name) use ($fileName) {
                // Check if file starts with the same base name
                return stripos($name, $fileName) === 0;
            });

            // ✨ DEBUG: Check if file exists anywhere in the list (case-insensitive)
            $exactMatches = array_filter($allFileNames, function($name) use ($fileName) {
                // Remove extension and compare
                $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
                return strcasecmp($nameWithoutExt, $fileName) === 0;
            });

            // ✨ DEBUG: Export ALL filenames to log for troubleshooting (only first time)
            static $fullListLogged = false;
            if (!$fullListLogged && count($allFileNames) < 200) {
                Log::debug("Full file list from Drive folder", [
                    'folder_id' => $folderId,
                    'total_files' => count($allFileNames),
                    'all_files' => $allFileNames
                ]);
                $fullListLogged = true;
            }

            Log::warning("File not found after trying all extensions", [
                'folder_id' => $folderId,
                'searched_name' => $fileName,
                'searched_name_hex' => bin2hex($fileName),
                'tried_extensions' => $extensions,
                'available_files_count' => count($this->cachedDriveFiles),
                'sample_files' => array_slice($allFileNames, 0, 10),
                'similar_files_found' => array_values($similarFiles), // Files that start with same name
                'exact_matches_any_ext' => array_values($exactMatches) // Files with exact base name
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

            // List all files in folder with pagination
            $query = "'{$folderId}' in parents and trashed=false";
            $fileList = [];
            $pageToken = null;

            do {
                $params = [
                    'q' => $query,
                    'fields' => 'nextPageToken, files(id,name,mimeType)',
                    'pageSize' => 1000, // Max allowed by Google Drive API
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $results = $drive->files->listFiles($params);
                $files = $results->getFiles();

                foreach ($files as $file) {
                    $fileList[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType()
                    ];
                }

                $pageToken = $results->getNextPageToken();

                Log::debug("Fetched page from Drive", [
                    'folder_id' => $folderId,
                    'files_in_page' => count($files),
                    'total_so_far' => count($fileList),
                    'has_next_page' => !empty($pageToken)
                ]);

            } while ($pageToken);

            Log::info("All files fetched from Drive folder", [
                'folder_id' => $folderId,
                'total_files' => count($fileList)
            ]);

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
     * ⚠️ DEPRECATED: No longer used after OPTIMIZATION 1 & 2
     * Kept for backward compatibility only
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

    /**
     * Build folder path for Google Drive storage
     * Same structure as FileUploadService: aergas/{MODULE}/{reff}__{slug-nama}
     */
    private function buildFolderPath(string $module, string $reffId, ?string $customerName = null): string
    {
        $module = strtoupper($module); // SK / SR
        $slug   = $customerName ? \Illuminate\Support\Str::slug($customerName, '_') : null;
        $leaf   = $slug ? "{$reffId}__{$slug}" : $reffId;
        return "aergas/{$module}/{$leaf}";
    }
}