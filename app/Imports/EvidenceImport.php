<?php

namespace App\Imports;

use App\Models\SkData;
use App\Models\SrData;
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

class EvidenceImport implements ToCollection, WithHeadingRow
{
    private bool $dryRun;
    private int $headingRow;
    private string $driveFolderLink;
    private string $module; // ✨ NEW: Module selection (SK or SR)
    private string $evidenceType;
    private bool $forceUpdate;
    private int $userId;
    private PhotoApprovalService $photoApprovalService;
    private GoogleDriveService $driveService;
    private FileUploadService $fileUploadService;

    // Optimization: Cache Drive folder file list
    private ?array $cachedDriveFiles = null;
    private ?string $cachedFolderId = null;

    // Progress tracking
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
        string $module, // ✨ NEW: Module parameter
        bool $dryRun = false,
        int $headingRow = 1,
        string $evidenceType = 'berita_acara',
        bool $forceUpdate = false,
        ?int $userId = null,
        ?PhotoApprovalService $photoApprovalService = null,
        ?GoogleDriveService $driveService = null,
        ?FileUploadService $fileUploadService = null
    ) {
        $this->driveFolderLink = trim($driveFolderLink);
        $this->module = strtoupper($module); // ✨ SK or SR
        $this->dryRun = $dryRun;
        $this->headingRow = $headingRow;
        $this->evidenceType = $evidenceType;
        $this->forceUpdate = $forceUpdate;
        $this->userId = $userId ?? (auth()->check() ? auth()->id() : 1);
        $this->photoApprovalService = $photoApprovalService ?? app(PhotoApprovalService::class);
        $this->driveService = $driveService ?? app(GoogleDriveService::class);
        $this->fileUploadService = $fileUploadService ?? app(FileUploadService::class);
        $this->startTime = microtime(true);
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();
        $nonEmptyRows = $rows->filter(fn($row) => !empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== '')));

        Log::info("Starting bulk evidence import", [
            'module' => $this->module,
            'evidence_type' => $this->evidenceType,
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

            // Log progress every 10 rows
            if ($this->processedRows % 10 === 0) {
                $progress = round(($this->processedRows / $this->totalRows) * 100, 1);
                $elapsed = round(microtime(true) - $this->startTime, 2);
                $avgTime = $this->processedRows > 0 ? round($elapsed / $this->processedRows, 2) : 0;
                $remaining = $this->totalRows - $this->processedRows;
                $eta = round($remaining * $avgTime);

                Log::info("Bulk evidence import progress", [
                    'module' => $this->module,
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
                Log::error('EvidenceImport row failed', [
                    'module' => $this->module,
                    'row' => $rowNo,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Final performance summary
        $totalTime = microtime(true) - $this->startTime;
        $this->results['performance']['total_time'] = round($totalTime, 2);
        $this->results['performance']['avg_time_per_row'] = $this->processedRows > 0
            ? round($totalTime / $this->processedRows, 2)
            : 0;

        Log::info("Bulk evidence import completed", [
            'module' => $this->module,
            'evidence_type' => $this->evidenceType,
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

        // Extract data - support both nama_ba and nama_file
        $reffId = $this->toString($normalized['reff_id'] ?? $normalized['reff id'] ?? $normalized['id reff'] ?? null);
        $namaFile = $this->toString(
            $normalized['nama_file'] ??
            $normalized['nama file'] ??
            $normalized['nama_ba'] ??
            $normalized['nama ba'] ??
            null
        );

        // Validate data
        $validator = Validator::make([
            'reff_id' => $reffId,
            'nama_file' => $namaFile,
        ], [
            'reff_id' => ['required', 'string'],
            'nama_file' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => $validator->errors()->all(),
                'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile]
            ];
            return;
        }

        // Check if customer exists
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        if (!$customer) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => ["Customer dengan reff_id {$reffId} tidak ditemukan"],
                'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile]
            ];
            return;
        }

        // ✨ NEW: Check/create module data based on module type (SK or SR)
        $moduleData = $this->getOrCreateModuleData($reffId);
        if (!$moduleData) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => ["Gagal membuat {$this->module} Data untuk customer {$reffId}"],
                'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile]
            ];
            return;
        }

        $moduleDataCreated = $moduleData['created'];
        $dataRecord = $moduleData['data'];

        // Check if evidence already exists
        $existingEvidence = PhotoApproval::where('reff_id_pelanggan', $reffId)
            ->where('module_name', strtolower($this->module))
            ->where('photo_field_name', $this->evidenceType)
            ->first();

        if ($existingEvidence && !$this->forceUpdate) {
            $this->results['skipped']++;
            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_file' => $namaFile,
                'status' => 'skipped',
                'message' => "Evidence {$this->evidenceType} sudah ada (gunakan Force Update untuk menimpa)"
            ];
            return;
        }

        $isReplacement = $existingEvidence && $this->forceUpdate;

        // Dry run mode - validate file exists in Drive first
        if ($this->dryRun) {
            $photoExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'JPG', 'JPEG', 'PNG', 'PDF'];
            $sourceFolderId = $this->extractFolderIdFromLink($this->driveFolderLink);

            if (!$sourceFolderId) {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => ["Tidak dapat extract folder ID dari link: {$this->driveFolderLink}"],
                    'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile]
                ];
                return;
            }

            $findResult = $this->findFileInFolder($sourceFolderId, $namaFile, $photoExtensions);

            if ($findResult) {
                [$fileId, $actualFileName] = $findResult;

                $this->results['success']++;

                $dryRunMessage = '✅ File ditemukan di Drive';
                if ($moduleDataCreated) {
                    $dryRunMessage .= " - {$this->module} Data akan dibuat otomatis";
                }
                if ($isReplacement) {
                    $dryRunMessage .= ' - AKAN MENIMPA evidence yang ada (Force Update)';
                }

                $this->results['details'][] = [
                    'row' => $rowNo,
                    'reff_id' => $reffId,
                    'nama_file' => $namaFile,
                    'file_found' => true,
                    'drive_filename' => $actualFileName,
                    'drive_file_id' => $fileId,
                    'module_data_exists' => !$moduleDataCreated,
                    'will_replace' => $isReplacement,
                    'status' => 'validated',
                    'message' => $dryRunMessage,
                    'module' => $this->module
                ];
            } else {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => ["❌ File dengan nama {$namaFile} tidak ditemukan di folder Drive"],
                    'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile, 'drive_folder' => $this->driveFolderLink]
                ];
            }

            return;
        }

        // Commit mode - actually upload the file
        $photoExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'JPG', 'JPEG', 'PNG', 'PDF'];

        try {
            $sourceFolderId = $this->extractFolderIdFromLink($this->driveFolderLink);

            if (!$sourceFolderId) {
                throw new \Exception("Tidak dapat extract folder ID dari link: {$this->driveFolderLink}");
            }

            $findResult = $this->findFileInFolder($sourceFolderId, $namaFile, $photoExtensions);

            if (!$findResult) {
                throw new \Exception("File dengan nama {$namaFile} tidak ditemukan di folder Drive");
            }

            [$sourceFileId, $originalFileName] = $findResult;

            $customerName = $customer ? $customer->nama_pelanggan : null;

            // If force update, delete old file from storage first
            if ($isReplacement && $existingEvidence) {
                try {
                    $this->fileUploadService->deleteExistingPhoto($reffId, $this->module, $this->evidenceType);

                    Log::info('Force update: Deleted old evidence', [
                        'reff_id' => $reffId,
                        'module' => $this->module,
                        'evidence_type' => $this->evidenceType,
                        'old_photo_id' => $existingEvidence->id
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Force update: Failed to delete old file (continuing anyway)', [
                        'reff_id' => $reffId,
                        'module' => $this->module,
                        'evidence_type' => $this->evidenceType,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Direct copy within Drive (no download/upload!)
            Log::info("Starting DIRECT COPY from Drive to Drive", [
                'reff_id' => $reffId,
                'module' => $this->module,
                'source_file_id' => $sourceFileId,
                'source_filename' => $originalFileName,
                'evidence_type' => $this->evidenceType
            ]);

            $copyStartTime = microtime(true);

            $folderPath = $this->buildFolderPath($this->module, $reffId, $customerName);
            $targetFolderId = $this->driveService->ensureNestedFolders($folderPath);

            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $slotSlug = \Illuminate\Support\Str::slug($this->evidenceType, '_');
            $ts = now()->format('Ymd_His');
            $targetFileName = "{$reffId}_{$slotSlug}_{$ts}.{$ext}";

            $copyResult = $this->driveService->copyFileDirect(
                sourceFileId: $sourceFileId,
                targetFolderId: $targetFolderId,
                newFileName: $targetFileName
            );

            $copyTime = microtime(true) - $copyStartTime;

            Log::info("DIRECT COPY completed", [
                'reff_id' => $reffId,
                'module' => $this->module,
                'new_file_id' => $copyResult['id'],
                'new_filename' => $copyResult['name'],
                'copy_time' => round($copyTime, 2) . 's'
            ]);

            $uploadResult = [
                'url' => $copyResult['webViewLink'] ?? $copyResult['webContentLink'] ?? '',
                'disk' => 'gdrive',
                'drive_file_id' => $copyResult['id'],
                'file_path' => $folderPath . '/' . $copyResult['name'],
                'file_name' => $copyResult['name'],
                'mime_type' => $copyResult['mimeType'] ?? 'image/jpeg',
                'file_size' => $copyResult['size'] ?? 0
            ];

            if (!$uploadResult || empty($uploadResult['url'])) {
                throw new \Exception("Gagal copy file menggunakan Google Drive direct copy");
            }

            // Update or Create PhotoApproval record
            if ($isReplacement && $existingEvidence) {
                // Force update: Update existing record and reset status
                $updateData = [
                    'photo_url' => $uploadResult['url'],
                    'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                    'drive_link' => $uploadResult['url'] ?? null,
                    'storage_disk' => $uploadResult['disk'] ?? 'gdrive',
                    'storage_path' => $uploadResult['file_path'] ?? '',

                    // AI validation - auto pass
                    'ai_status' => 'passed',
                    'ai_score' => 1.0,
                    'ai_notes' => "Evidence {$this->evidenceType} - Force updated from bulk import, auto-passed",
                    'ai_last_checked_at' => now(),

                    // Reset status to tracer_pending
                    'photo_status' => 'tracer_pending',

                    // Upload info
                    'uploaded_by' => $this->userId,
                    'uploaded_at' => now(),
                ];

                // Reset approval fields only if columns exist
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
                    'module' => $this->module,
                    'evidence_type' => $this->evidenceType
                ]);
            } else {
                // Create new PhotoApproval record
                $photoApproval = PhotoApproval::create([
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => strtolower($this->module), // sk or sr
                    'photo_field_name' => $this->evidenceType,
                    'photo_url' => $uploadResult['url'],
                    'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                    'drive_link' => $uploadResult['url'] ?? null,
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

            // Create FileStorage record
            try {
                \App\Models\FileStorage::create([
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => strtolower($this->module),
                    'field_name' => $this->evidenceType,
                    'original_filename' => $originalFileName,
                    'stored_filename' => $uploadResult['file_name'] ?? $targetFileName,
                    'file_path' => $uploadResult['file_path'],
                    'mime_type' => $uploadResult['mime_type'] ?? 'image/jpeg',
                    'file_size' => $uploadResult['file_size'] ?? 0,
                    'file_hash' => null,
                    'google_drive_id' => $uploadResult['drive_file_id'],
                    'uploaded_by' => $this->userId,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FileStorage record creation failed (non-fatal)', [
                    'reff_id' => $reffId,
                    'module' => $this->module,
                    'error' => $e->getMessage()
                ]);
            }

            // Sync module status
            if ($dataRecord) {
                $dataRecord->syncModuleStatusFromPhotos();
            }

            $this->results['success']++;

            $message = "Evidence {$this->evidenceType} berhasil di-copy dari Drive folder";
            if ($isReplacement) {
                $message .= ' (FORCE UPDATE - Status di-reset ke Tracer Pending)';
            }
            if ($moduleDataCreated) {
                $message .= " ({$this->module} Data otomatis dibuat)";
            }

            $this->results['details'][] = [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'nama_file' => $namaFile,
                'photo_id' => $photoApproval->id,
                'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                'module_data_created' => $moduleDataCreated,
                'evidence_type' => $this->evidenceType,
                'force_updated' => $isReplacement,
                'status' => $isReplacement ? 'force_updated' : 'uploaded',
                'message' => $message,
                'module' => $this->module
            ];

            Log::info('Evidence imported successfully from Drive', [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'module' => $this->module,
                'photo_id' => $photoApproval->id,
                'evidence_type' => $this->evidenceType
            ]);

        } catch (\Throwable $e) {
            $this->results['failed'][] = [
                'row' => $rowNo,
                'errors' => ['Copy dari Drive gagal: ' . $e->getMessage()],
                'data' => ['reff_id' => $reffId, 'nama_file' => $namaFile, 'drive_folder' => $this->driveFolderLink]
            ];

            Log::error('Evidence upload failed', [
                'row' => $rowNo,
                'reff_id' => $reffId,
                'module' => $this->module,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✨ NEW: Get or create module data (SK, SR, or GAS_IN) based on module type
     */
    private function getOrCreateModuleData(string $reffId): ?array
    {
        if ($this->module === 'SK') {
            $data = SkData::where('reff_id_pelanggan', $reffId)->first();
            $created = false;

            if (!$data) {
                try {
                    $data = SkData::create([
                        'reff_id_pelanggan' => $reffId,
                        'tanggal_instalasi' => now(),
                        'status' => 'draft',
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ]);
                    $created = true;

                    Log::info('Auto-created SK Data for evidence import', [
                        'reff_id' => $reffId,
                        'sk_id' => $data->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create SK Data', [
                        'reff_id' => $reffId,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            }

            return ['data' => $data, 'created' => $created];
        } elseif ($this->module === 'SR') {
            $data = SrData::where('reff_id_pelanggan', $reffId)->first();
            $created = false;

            if (!$data) {
                try {
                    $data = SrData::create([
                        'reff_id_pelanggan' => $reffId,
                        'tanggal_pemasangan' => now(),
                        'status' => 'draft',
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ]);
                    $created = true;

                    Log::info('Auto-created SR Data for evidence import', [
                        'reff_id' => $reffId,
                        'sr_id' => $data->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create SR Data', [
                        'reff_id' => $reffId,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            }

            return ['data' => $data, 'created' => $created];
        } elseif ($this->module === 'GAS_IN') {
            $data = \App\Models\GasInData::where('reff_id_pelanggan', $reffId)->first();
            $created = false;

            if (!$data) {
                try {
                    $data = \App\Models\GasInData::create([
                        'reff_id_pelanggan' => $reffId,
                        'tanggal_gas_in' => now(),
                        'status' => 'draft',
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ]);
                    $created = true;

                    Log::info('Auto-created GAS IN Data for evidence import', [
                        'reff_id' => $reffId,
                        'gas_in_id' => $data->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create GAS IN Data', [
                        'reff_id' => $reffId,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            }

            return ['data' => $data, 'created' => $created];
        }

        return null;
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

    private function extractFolderIdFromLink(string $link): ?string
    {
        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $link)) {
            return $link;
        }

        return null;
    }

    private function findFileInFolder(string $folderId, string $fileName, array $extensions): ?array
    {
        try {
            if ($this->cachedDriveFiles === null || $this->cachedFolderId !== $folderId) {
                $this->cachedDriveFiles = $this->listAllFilesInFolder($folderId);
                $this->cachedFolderId = $folderId;
            } else {
                $this->results['performance']['cache_hits']++;
                $this->results['performance']['api_calls_saved']++;
            }

            foreach ($extensions as $ext) {
                $fullName = $fileName . '.' . $ext;

                foreach ($this->cachedDriveFiles as $file) {
                    if (strcasecmp($file['name'], $fullName) === 0) {
                        return [$file['id'], $file['name']];
                    }
                }
            }

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

            $query = "'{$folderId}' in parents and trashed=false";
            $fileList = [];
            $pageToken = null;

            do {
                $params = [
                    'q' => $query,
                    'fields' => 'nextPageToken, files(id,name,mimeType)',
                    'pageSize' => 1000,
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

            } while ($pageToken);

            return $fileList;

        } catch (\Exception $e) {
            Log::error("Error listing files in Drive folder", [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function buildFolderPath(string $module, string $reffId, ?string $customerName = null): string
    {
        $module = strtoupper($module);
        $slug   = $customerName ? \Illuminate\Support\Str::slug($customerName, '_') : null;
        $leaf   = $slug ? "{$reffId}__{$slug}" : $reffId;
        return "aergas/{$module}/{$leaf}";
    }
}
