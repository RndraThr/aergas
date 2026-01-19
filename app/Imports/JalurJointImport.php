<?php

namespace App\Imports;

use App\Models\JalurJointData;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Models\JalurLineNumber;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JalurJointImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private bool $dryRun;
    private array $results = [];
    private string $filePath;
    private array $hyperlinks = [];
    private array $rowMapping = []; // Maps joint_number+date to actual Excel row
    private GoogleDriveService $googleDriveService;

    public function __construct(bool $dryRun = false, string $filePath = '')
    {
        $this->dryRun = $dryRun;
        $this->filePath = $filePath;
        $this->googleDriveService = app(GoogleDriveService::class);

        // Extract all hyperlinks from Excel file
        if ($filePath && file_exists($filePath)) {
            $this->extractAllHyperlinks();
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $data) {
            try {
                // Skip empty rows
                if (empty($data['joint_number']) || trim($data['joint_number']) === '') {
                    continue;
                }

                // Get actual Excel row number from rowMapping
                $jointNumber = $data['joint_number'] ?? null;
                $tanggal = $data['tanggal_joint'] ?? null;

                // Default to old method if mapping not available
                $excelRowNumber = $index + 2;

                if ($jointNumber && $tanggal) {
                    // Convert tanggal to Y-m-d format if needed
                    if (is_numeric($tanggal)) {
                        try {
                            $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Keep original if conversion fails
                        }
                    }

                    $mappingKey = $jointNumber . '|' . $tanggal;
                    if (isset($this->rowMapping[$mappingKey])) {
                        $excelRowNumber = $this->rowMapping[$mappingKey];
                        Log::info("Using mapped row number for joint", [
                            'collection_index' => $index,
                            'joint_number' => $jointNumber,
                            'tanggal' => $tanggal,
                            'actual_excel_row' => $excelRowNumber
                        ]);
                    }
                }

                $result = $this->processRow($data, $excelRowNumber);
                $this->results[] = $result;

            } catch (\Exception $e) {
                $this->results[] = [
                    'row' => $excelRowNumber ?? $index + 2,
                    'status' => 'error',
                    'data' => $data->toArray(),
                    'message' => $e->getMessage(),
                ];

                Log::error('Joint import error', [
                    'row' => $excelRowNumber,
                    'data' => $data->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function processRow(Collection $data, int $excelRowNumber): array
    {
        // 1. Parse joint_number
        $jointNumber = trim($data['joint_number']);
        $parsed = $this->parseJointNumber($jointNumber);

        if (!$parsed) {
            throw new \Exception("Format joint number tidak valid. Format yang benar: {CLUSTER}-{FITTING}{CODE} (Contoh: KRG-CP001) atau {FITTING}.{CODE} untuk diameter 180 (Contoh: BF.05)");
        }

        [$clusterCode, $fittingCode, $jointCode] = $parsed;

        // 2. Validate Cluster
        // If cluster code is 'NONE', get cluster from Excel 'cluster' column or line_number
        if ($clusterCode === 'NONE') {
            // Priority 1: Check explicit cluster column from Excel
            if (!empty($data['cluster'])) {
                $clusterCode = trim($data['cluster']);
            } 
            // Priority 2: Try to get cluster from line_from (e.g., 180-SLM-LN001 -> cluster SLM)
            elseif (!empty($data['joint_line_from'])) {
                $lineFrom = trim($data['joint_line_from']);
            
            // Priority 3: Check line_to as fallback
            $lineTo = trim($data['joint_line_to']); 

            if (!isset($clusterCode)) {
                if (preg_match('/^\d+-([A-Z]+)-LN\d+$/', $lineFrom ?? '', $lineMatches)) {
                    $clusterCode = $lineMatches[1];
                } elseif (preg_match('/^\d+-([A-Z]+)-LN\d+$/', $lineTo, $lineMatches)) {
                    $clusterCode = $lineMatches[1];
                } else {
                    throw new \Exception("Untuk format joint tanpa cluster ({$jointNumber}), harus ada kolom 'Cluster' di Excel, atau cluster di joint_line_from/joint_line_to. Format Line: {DIAMETER}-{CLUSTER}-LN{NUMBER}");
                }
            }
        }

        $cluster = JalurCluster::where('code_cluster', $clusterCode)->first();
        if (!$cluster) {
            throw new \Exception("Cluster dengan code '{$clusterCode}' tidak ditemukan di database");
        }

        // 3. Validate Fitting Type
        // For diameter 180 format (DIAMETER_180 marker), use a default fitting type
        if ($fittingCode === 'DIAMETER_180') {
            // For diameter 180, fitting type is not part of joint number
            // Use a generic/default fitting type or get from Excel if available
            // Check if there's a fitting type column in Excel
            $fittingTypeCode = !empty($data['fitting_type']) ? trim($data['fitting_type']) : null;

            if ($fittingTypeCode) {
                // Try to get fitting type from Excel column
                $fittingType = JalurFittingType::where('code_fitting', $fittingTypeCode)->first();
                if (!$fittingType) {
                    throw new \Exception("Fitting Type dengan code '{$fittingTypeCode}' tidak ditemukan di database");
                }
            } else {
                // Use default fitting type for diameter 180 (e.g., 'CP' for Coupler)
                // Or create a special 'GENERIC' fitting type
                $fittingType = JalurFittingType::where('code_fitting', 'CP')->first();
                if (!$fittingType) {
                    throw new \Exception("Untuk diameter 180, fitting type default 'CP' tidak ditemukan. Tambahkan kolom 'fitting_type' di Excel atau buat fitting type 'CP' di database.");
                }
            }
            // Reset fittingCode for later use
            $fittingCode = $fittingType->code_fitting;
        } else {
            // Standard format: fitting type is part of joint number
            $fittingType = JalurFittingType::where('code_fitting', $fittingCode)->first();
            if (!$fittingType) {
                throw new \Exception("Fitting Type dengan code '{$fittingCode}' tidak ditemukan di database");
            }
        }

        // 4. Validate required fields
        $this->validateRequiredFields($data);

        // 5. Convert tanggal_joint
        $tanggalJoint = $data['tanggal_joint'];
        if (is_numeric($tanggalJoint) && $tanggalJoint > 0) {
            try {
                $tanggalJoint = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalJoint)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Failed to convert Excel date", ['value' => $tanggalJoint, 'row' => $excelRowNumber]);
            }
        }

        // 6. Validate diameter
        $diameter = (string) $data['diameter'];
        if (!in_array($diameter, ['63', '90', '110', '160', '180', '200'])) {
            throw new \Exception("Diameter tidak valid. Pilihan: 63, 90, 110, 160, 180, 200");
        }

        // 7. Validate Line Numbers
        $jointLineFrom = trim($data['joint_line_from']);
        $jointLineTo = trim($data['joint_line_to']);
        $jointLineOptional = !empty($data['joint_line_optional']) ? trim($data['joint_line_optional']) : null;

        // Validate joint_line_from (skip validation if "EXISTING")
        if (strtoupper($jointLineFrom) !== 'EXISTING') {
            $lineFrom = JalurLineNumber::where('line_number', $jointLineFrom)
                ->where('cluster_id', $cluster->id)
                // Removed diameter check to allow Reducer/Cross-diameter joints
                ->first();

            if (!$lineFrom) {
                throw new \Exception("Line '{$jointLineFrom}' tidak ditemukan di cluster {$cluster->nama_cluster}. Pastikan Line Number benar.");
            }
        } else {
            $lineFrom = null; // Mark as null/existing
        }

        // Validate joint_line_to (skip validation if "EXISTING")
        if (strtoupper($jointLineTo) !== 'EXISTING') {
            $lineTo = JalurLineNumber::where('line_number', $jointLineTo)
                ->where('cluster_id', $cluster->id)
                // Removed diameter check
                ->first();

            if (!$lineTo) {
                throw new \Exception("Line '{$jointLineTo}' tidak ditemukan di cluster {$cluster->nama_cluster}. Pastikan Line Number benar.");
            }
        } else {
            $lineTo = null;
        }

        // Check for Reducer logic (Different Diameters)
        if ($lineFrom && $lineTo) {
            if ($lineFrom->diameter != $lineTo->diameter) {
                // Validate fitting type is Reducer (RD) or allow with warning
                if (strpos($fittingType->code_fitting, 'RD') === false) {
                    // Not a Reducer but diameters differ.
                    // Allow it but log warning
                    Log::warning("Joint {$jointNumber} connects different diameters ({$lineFrom->diameter} vs {$lineTo->diameter}) but fitting type is {$fittingType->code_fitting} (not RD).");
                }
            }
        }

        // 9. Validate Equal Tee (TE) - optional 3rd line
        if ($fittingType->code_fitting === 'TE' && !empty($jointLineOptional)) {
            // Validate joint_line_optional only if provided (skip validation if "EXISTING")
            if (strtoupper($jointLineOptional) !== 'EXISTING') {
                $lineOptional = JalurLineNumber::where('line_number', $jointLineOptional)
                    ->where('cluster_id', $cluster->id)
                    // Removed diameter check
                    ->first();

                if (!$lineOptional) {
                    throw new \Exception("Line '{$jointLineOptional}' tidak ditemukan di cluster {$cluster->nama_cluster}.");
                }
            }
        }

        // 10. Validate tipe_penyambungan
        $tipePenyambungan = strtoupper(trim($data['tipe_penyambungan']));
        if (!in_array($tipePenyambungan, ['EF', 'BF'])) {
            throw new \Exception("Tipe penyambungan harus 'EF' atau 'BF'");
        }

        // 11. Extract hyperlink for foto from joint_number cell (optional)
        $fotoHyperlink = $this->getHyperlink($excelRowNumber, 'joint_number');

        Log::info("Processing joint row", [
            'row' => $excelRowNumber,
            'joint_number' => $jointNumber,
            'cluster' => $clusterCode,
            'fitting' => $fittingCode,
            'foto_hyperlink' => $fotoHyperlink ?? 'no photo',
        ]);

        // DRY RUN - Don't save to database
        if ($this->dryRun) {
            return [
                'row' => $excelRowNumber,
                'status' => 'success',
                'data' => [
                    'joint_number' => $jointNumber,
                    'cluster' => $cluster->nama_cluster,
                    'fitting_type' => $fittingType?->nama_fitting ?? '-',
                    'tanggal_joint' => $tanggalJoint,
                    'joint_line_from' => $jointLineFrom,
                    'joint_line_to' => $jointLineTo,
                    'joint_line_optional' => $jointLineOptional,
                    'tipe_penyambungan' => $tipePenyambungan,
                    'foto_hyperlink' => $fotoHyperlink,
                    'keterangan' => $data['keterangan'] ?? null,
                ],
                'message' => 'Valid - Ready to import',
            ];
        }

        // REAL IMPORT - Save to database
        DB::beginTransaction();
        try {
            // Check for existing joint data by nomor_joint only
            // Each joint number represents a unique physical joint
            // Different joints (BF.05, BF.06) can have same line combinations (e.g., diameter 180 case)
            $existingJoint = JalurJointData::where('nomor_joint', $jointNumber)->first();

            if ($existingJoint) {
                // Record exists - apply Smart Update logic
                $updateResult = $this->smartUpdateJoint($existingJoint, [
                    'cluster_id' => $cluster->id,
                    'fitting_type_id' => $fittingType->id,
                    'joint_code' => $jointCode,
                    'tanggal_joint' => $tanggalJoint,
                    'joint_line_from' => $jointLineFrom,
                    'joint_line_to' => $jointLineTo,
                    'joint_line_optional' => $jointLineOptional,
                    'tipe_penyambungan' => $tipePenyambungan,
                    'keterangan' => $data['keterangan'] ?? null,
                    'foto_hyperlink' => $fotoHyperlink,
                ], $excelRowNumber);

                $joint = $existingJoint;

                Log::info("Joint import " . $updateResult['action'], [
                    'row' => $excelRowNumber,
                    'joint_id' => $joint->id,
                    'result' => $updateResult
                ]);
            } else {
                // No existing record - create new
                $joint = JalurJointData::create([
                    'nomor_joint' => $jointNumber,
                    'cluster_id' => $cluster->id,
                    'fitting_type_id' => $fittingType->id,
                    'joint_code' => $jointCode,
                    'tanggal_joint' => $tanggalJoint,
                    'joint_line_from' => $jointLineFrom,
                    'joint_line_to' => $jointLineTo,
                    'joint_line_optional' => $jointLineOptional,
                    'tipe_penyambungan' => $tipePenyambungan,
                    'keterangan' => $data['keterangan'] ?? null,
                    'status_laporan' => 'draft',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // Handle photo from Google Drive hyperlink (only for new records and if hyperlink exists)
                if ($fotoHyperlink) {
                    $this->handlePhotoFromDriveLink($joint, $fotoHyperlink, 'foto_evidence_joint');
                }
            }

            DB::commit();

            return [
                'row' => $excelRowNumber,
                'status' => 'success',
                'data' => [
                    'joint_id' => $joint->id,
                    'joint_number' => $jointNumber,
                    'cluster' => $cluster->nama_cluster,
                    'fitting_type' => $fittingType?->nama_fitting ?? '-',
                    'tanggal_joint' => $tanggalJoint,
                ],
                'message' => 'Joint berhasil diimport',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function parseJointNumber(string $jointNumber): ?array
    {
        // Format 1: {CLUSTER}-{FITTING}{CODE}
        // Contoh: KRG-CP001, GDK-EL90124, KRG-TE002
        // Note: Elbow has angle in code: EL90 (Elbow 90), EL45 (Elbow 45)

        // Format 2 (Diameter 180): {FITTING}.{CODE} or {FITTING}{CODE}
        // Contoh: BF.05, EF.010, BF.011
        // Joint tanpa cluster, biasanya untuk diameter besar (180)

        // Try Format 1: CLUSTER-FITTING_CODE+NUMBER
        if (preg_match('/^([A-Z]+)-([A-Z]+\d*)(\d{3,})$/', $jointNumber, $matches)) {
            return [
                $matches[1], // Cluster Code (KRG, GDK)
                $matches[2], // Fitting Code (CP, EL90, EL45, TE, RD, FA, VL, TF, TS, ECP)
                $matches[3], // Joint Number (001, 124, etc - minimum 3 digits)
            ];
        }

        // Try Format 2: TIPE_PENYAMBUNGAN.CODE (untuk diameter 180)
        // Contoh: BF.05, EF.010
        // BF/EF adalah tipe penyambungan (Butt Fusion / Electro Fusion), BUKAN fitting type
        if (preg_match('/^([A-Z]+)[\.\-]?(\d{1,})$/', $jointNumber, $matches)) {
            // For diameter 180, fitting type must be specified in separate Excel column
            // Return special markers to indicate diameter 180 format
            return [
                'NONE',          // No cluster in joint number (will use from line_from)
                'DIAMETER_180',  // Special marker: fitting type from Excel column, not from joint number
                $matches[0],     // Full joint number as code (BF.05, EF.010, etc)
            ];
        }

        return null;
    }

    private function validateRequiredFields(Collection $data): void
    {
        $required = [
            'joint_number' => 'Nomor Joint',
            'tanggal_joint' => 'Tanggal Joint',
            'diameter' => 'Diameter',
            'joint_line_from' => 'Joint Line From',
            'joint_line_to' => 'Joint Line To',
            'tipe_penyambungan' => 'Tipe Penyambungan',
        ];

        foreach ($required as $field => $label) {
            if (empty($data[$field]) || trim($data[$field]) === '') {
                throw new \Exception("Field '{$label}' wajib diisi");
            }
        }
    }

    private function handlePhotoFromDriveLink(JalurJointData $joint, string $driveLink, string $fieldName): void
    {
        try {
            // Generate folder path: jalur_joint/{cluster}/{joint_number}/{date}
            $clusterSlug = \Illuminate\Support\Str::slug($joint->cluster->nama_cluster, '_');
            $dateFolder = $joint->tanggal_joint->format('Y-m-d');
            $customPath = "jalur_joint/{$clusterSlug}/{$joint->nomor_joint}/{$dateFolder}";

            // Generate unique filename
            $timestamp = now()->format('YmdHis');
            $customFileName = "{$fieldName}_{$timestamp}";

            // Copy file from Google Drive link to project folder
            $result = $this->googleDriveService->copyFromDriveLink(
                $driveLink,
                $customPath,
                $customFileName
            );

            // Create PhotoApproval record
            PhotoApproval::create([
                'module_name' => 'jalur_joint',
                'module_record_id' => $joint->id,
                'photo_field_name' => $fieldName,
                'photo_url' => $result['url'],
                'drive_file_id' => $result['id'] ?? null,
                'photo_status' => 'tracer_pending',
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
            ]);

            Log::info('Photo copied from Google Drive link', [
                'joint_id' => $joint->id,
                'field_name' => $fieldName,
                'drive_link' => $driveLink,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to copy photo from Google Drive link', [
                'joint_id' => $joint->id,
                'field_name' => $fieldName,
                'drive_link' => $driveLink,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Gagal copy foto {$fieldName} dari Google Drive: " . $e->getMessage());
        }
    }

    /**
     * Extract all hyperlinks from Excel file using PhpSpreadsheet
     */
    private function extractAllHyperlinks(): void
    {
        try {
            // Load Excel file directly using PhpSpreadsheet
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Start from row 2 (skip header)
            for ($row = 2; $row <= $highestRow; $row++) {
                // Get joint_number (column A) and tanggal (column B) for mapping
                $jointNumberCell = $worksheet->getCell('A' . $row);
                $tanggalCell = $worksheet->getCell('B' . $row);

                $jointNumber = $jointNumberCell->getValue();
                $tanggal = $tanggalCell->getValue();

                // Convert Excel date to Y-m-d format
                if ($tanggal && is_numeric($tanggal)) {
                    try {
                        $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original value if conversion fails
                    }
                }

                // Create mapping key: joint_number|date
                if ($jointNumber && $tanggal) {
                    $mappingKey = $jointNumber . '|' . $tanggal;
                    $this->rowMapping[$mappingKey] = $row;
                }

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellCoordinate);

                    if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                        $url = $cell->getHyperlink()->getUrl();
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

                        if (!isset($this->hyperlinks[$row])) {
                            $this->hyperlinks[$row] = [];
                        }
                        $this->hyperlinks[$row][$columnLetter] = $url;

                        Log::info("Hyperlink found", [
                            'row' => $row,
                            'column' => $columnLetter,
                            'cell' => $cellCoordinate,
                            'url' => $url,
                            'cell_value' => $cell->getValue()
                        ]);
                    }
                }
            }

            Log::info("Total hyperlinks extracted", ['count' => count($this->hyperlinks)]);

        } catch (\Exception $e) {
            Log::error("Failed to extract hyperlinks", [
                'file' => $this->filePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get hyperlink from specific cell
     */
    private function getHyperlink(int $excelRow, string $columnName): ?string
    {
        // Map column names to Excel letters
        $columnMap = [
            'joint_number' => 'A', // Column A - hyperlink foto ada di cell joint_number
        ];

        $columnLetter = $columnMap[$columnName] ?? null;
        if (!$columnLetter) {
            return null;
        }

        return $this->hyperlinks[$excelRow][$columnLetter] ?? null;
    }

    /**
     * Smart Update Logic for Joint Data
     * Only update what changed, protect approved data
     */
    private function smartUpdateJoint($existingJoint, array $newData, int $excelRowNumber): array
    {
        $statusApproved = in_array($existingJoint->status_laporan, ['tracer_approved', 'cgp_approved', 'tracer_rejected', 'cgp_rejected']);

        // Define field categories
        $nonKrusialFields = ['keterangan'];
        $krusialDataFields = ['cluster_id', 'fitting_type_id', 'tanggal_joint', 'joint_line_from', 'joint_line_to', 'joint_line_optional', 'tipe_penyambungan'];

        // Check what changed
        $changedNonKrusial = [];
        $changedKrusial = [];

        // Check non-krusial fields
        if (($existingJoint->keterangan ?? '') !== ($newData['keterangan'] ?? '')) {
            $changedNonKrusial[] = 'keterangan';
        }

        // Check krusial data fields
        if ($existingJoint->cluster_id !== $newData['cluster_id']) {
            $changedKrusial[] = 'cluster_id';
        }
        if ($existingJoint->fitting_type_id !== $newData['fitting_type_id']) {
            $changedKrusial[] = 'fitting_type_id';
        }
        if ($existingJoint->tanggal_joint !== $newData['tanggal_joint']) {
            $changedKrusial[] = 'tanggal_joint';
        }
        if (($existingJoint->joint_line_from ?? '') !== ($newData['joint_line_from'] ?? '')) {
            $changedKrusial[] = 'joint_line_from';
        }
        if (($existingJoint->joint_line_to ?? '') !== ($newData['joint_line_to'] ?? '')) {
            $changedKrusial[] = 'joint_line_to';
        }
        if (($existingJoint->joint_line_optional ?? '') !== ($newData['joint_line_optional'] ?? '')) {
            $changedKrusial[] = 'joint_line_optional';
        }
        if (($existingJoint->tipe_penyambungan ?? '') !== ($newData['tipe_penyambungan'] ?? '')) {
            $changedKrusial[] = 'tipe_penyambungan';
        }

        // Check photo evidence changes (compare hyperlinks from photo_approvals)
        $photoChanged = $this->checkJointPhotoLinkChanged($existingJoint, $newData['foto_hyperlink']);

        // Decision logic
        if (empty($changedNonKrusial) && empty($changedKrusial) && !$photoChanged) {
            // No changes at all - SKIP
            return [
                'action' => 'skipped',
                'reason' => 'no_changes',
                'message' => 'Data sama persis, tidak ada perubahan'
            ];
        }

        if ($statusApproved) {
            // Data already approved - check what changed
            if (!empty($changedKrusial) || $photoChanged) {
                // Krusial fields or photos changed - CANNOT UPDATE
                return [
                    'action' => 'skipped',
                    'reason' => 'protected_approved_data',
                    'message' => 'Data sudah approved, tidak bisa mengubah data krusial atau foto',
                    'changed_krusial' => $changedKrusial,
                    'photo_changed' => $photoChanged
                ];
            }

            // Only non-krusial changed - safe to update
            if (!empty($changedNonKrusial)) {
                $updateData = [];
                if (in_array('keterangan', $changedNonKrusial)) {
                    $updateData['keterangan'] = $newData['keterangan'] ?? null;
                }

                $existingJoint->update($updateData);

                return [
                    'action' => 'updated',
                    'updated_fields' => $changedNonKrusial,
                    'message' => 'Updated non-krusial fields only (status tetap ' . $existingJoint->status_laporan . ')'
                ];
            }
        } else {
            // Status still draft - can update everything
            $updateData = [
                'cluster_id' => $newData['cluster_id'],
                'fitting_type_id' => $newData['fitting_type_id'],
                'joint_code' => $newData['joint_code'],
                'tanggal_joint' => $newData['tanggal_joint'],
                'joint_line_from' => $newData['joint_line_from'],
                'joint_line_to' => $newData['joint_line_to'],
                'joint_line_optional' => $newData['joint_line_optional'],
                'tipe_penyambungan' => $newData['tipe_penyambungan'],
                'keterangan' => $newData['keterangan'],
                'updated_by' => Auth::id(),
            ];

            $existingJoint->update($updateData);

            // Update photo if link changed
            if ($photoChanged) {
                $this->updateJointPhotoIfChanged($existingJoint, $newData['foto_hyperlink']);
            }

            return [
                'action' => 'updated',
                'updated_fields' => array_merge($changedNonKrusial, $changedKrusial),
                'photos_updated' => $photoChanged,
                'message' => 'Updated all changed fields (status: draft)'
            ];
        }

        return [
            'action' => 'skipped',
            'reason' => 'unknown',
            'message' => 'Tidak ada aksi yang dilakukan'
        ];
    }

    /**
     * Check if joint photo link changed
     */
    private function checkJointPhotoLinkChanged($joint, string $newPhotoLink): bool
    {
        // Get existing photo approval
        $existingPhoto = PhotoApproval::where('module_name', 'jalur_joint')
            ->where('module_record_id', $joint->id)
            ->where('photo_field_name', 'foto_evidence_joint')
            ->first();

        if ($existingPhoto && !empty($newPhotoLink)) {
            // Compare drive links
            if ($existingPhoto->drive_link !== $newPhotoLink) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update joint photo if link changed (only for draft status)
     */
    private function updateJointPhotoIfChanged($joint, string $newPhotoLink): void
    {
        // Delete old photo approval
        \App\Models\PhotoApproval::where('module_name', 'jalur_joint')
            ->where('module_record_id', $joint->id)
            ->delete();

        // Re-upload with new link
        if (!empty($newPhotoLink)) {
            $this->handlePhotoFromDriveLink($joint, $newPhotoLink, 'foto_evidence_joint');
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Chunk size for reading Excel file
     * Process 100 rows at a time to avoid memory issues
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
