<?php

namespace App\Imports;

use App\Models\JalurCluster;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JalurLoweringImport implements ToCollection, WithHeadingRow, WithEvents, WithChunkReading
{
    private bool $dryRun;
    private array $results = ['success' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => []];
    private array $hyperlinks = [];
    private array $rowMapping = []; // Maps line_number+date to actual Excel row
    private GoogleDriveService $googleDriveService;
    private $sheet;
    private string $filePath;

    public function __construct(bool $dryRun = false, string $filePath = '')
    {
        $this->dryRun = $dryRun;
        $this->filePath = $filePath;
        $this->googleDriveService = app(GoogleDriveService::class);
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheet = $event->getSheet()->getDelegate();

                // If filePath is provided, load Excel directly to extract hyperlinks
                if (!empty($this->filePath) && file_exists($this->filePath)) {
                    $this->extractAllHyperlinks();
                }
            },
        ];
    }

    /**
     * Extract all hyperlinks from Excel sheet before processing
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

            Log::info("Extracting hyperlinks from Excel", [
                'file' => $this->filePath,
                'highest_row' => $highestRow,
                'highest_column' => $highestColumn
            ]);

            // Start from row 2 (skip header)
            for ($row = 2; $row <= $highestRow; $row++) {
                // Get line_number (column C) and tanggal (column D) for mapping
                $lineNumberCell = $worksheet->getCell('C' . $row);
                $tanggalCell = $worksheet->getCell('D' . $row);

                $lineNumber = $lineNumberCell->getValue();
                $tanggal = $tanggalCell->getValue();

                // Convert Excel date to Y-m-d format
                if ($tanggal && is_numeric($tanggal)) {
                    try {
                        $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original value if conversion fails
                    }
                }

                // Create mapping key: line_number|date
                if ($lineNumber && $tanggal) {
                    $mappingKey = $lineNumber . '|' . $tanggal;
                    $this->rowMapping[$mappingKey] = $row;
                }

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellCoordinate);

                    // Check if cell has hyperlink
                    if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                        $url = $cell->getHyperlink()->getUrl();
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

                        // Store hyperlink with row and column reference
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

            Log::info("Total hyperlinks extracted", ['count' => count($this->hyperlinks), 'rows_with_links' => array_keys($this->hyperlinks)]);

        } catch (\Exception $e) {
            Log::error("Failed to extract hyperlinks", [
                'file' => $this->filePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get hyperlink for specific row and column
     */
    private function getHyperlink(int $excelRow, string $columnName): ?string
    {
        // Map column names to Excel letters
        // CATATAN: Mapping ini harus disesuaikan dengan urutan kolom Excel user
        // Jika user menggunakan format lama (tanpa nama_jalan di kolom E):
        //   F=lowering, G=bongkaran, I=cassing, J=marker_tape, L=concrete_slab, M=mc_100
        // Jika user menggunakan template baru (dengan nama_jalan di kolom E):
        //   G=lowering, H=bongkaran, J=cassing, K=marker_tape, L=concrete_slab, M=mc_0, N=mc_100

        $columnMap = [
            'lowering' => 'G',              // Kolom G (karena E=nama_jalan)
            'bongkaran' => 'H',             // Kolom H
            'cassing_quantity' => 'J',      // Kolom J (I=kedalaman)
            'marker_tape_quantity' => 'K',  // Kolom K
            'concrete_slab_quantity' => 'L', // Kolom L
            'landasan_quantity' => 'M',     // Kolom M (New Position)
            'mc_0' => 'N',                  // Kolom N (Shifted from M)
            'mc_100' => 'O',                // Kolom O (Shifted from N)
        ];

        $columnLetter = $columnMap[$columnName] ?? null;

        if (!$columnLetter) {
            return null;
        }

        return $this->hyperlinks[$excelRow][$columnLetter] ?? null;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== ''))) {
                $this->results['skipped']++;
                continue;
            }

            // Get actual Excel row number from rowMapping using line_number + date
            $lineNumber = $row['line_number'] ?? null;
            $tanggal = $row['tanggal_jalur'] ?? null;

            // Default to old method if mapping not available
            $excelRowNumber = $index + 2;

            if ($lineNumber && $tanggal) {
                // Convert tanggal to Y-m-d format if needed
                if (is_numeric($tanggal)) {
                    try {
                        $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original if conversion fails
                    }
                }

                $mappingKey = $lineNumber . '|' . $tanggal;
                if (isset($this->rowMapping[$mappingKey])) {
                    $excelRowNumber = $this->rowMapping[$mappingKey];
                    Log::info("Using mapped row number", [
                        'collection_index' => $index,
                        'line_number' => $lineNumber,
                        'tanggal' => $tanggal,
                        'actual_excel_row' => $excelRowNumber
                    ]);
                }
            }

            try {
                [$isValid, $data, $errors] = $this->validateRow($row->toArray(), $excelRowNumber);

                if (!$isValid) {
                    $this->results['failed'][] = [
                        'row' => $excelRowNumber,
                        'errors' => $errors,
                        'data' => $row->toArray()
                    ];
                    continue;
                }

                if ($this->dryRun) {
                    // Dry run mode - just validate without inserting
                    $this->results['success']++;
                    continue;
                }

                // Process and insert data
                $this->processRow($data, $excelRowNumber);
                $this->results['success']++;

            } catch (\Exception $e) {
                Log::error("Error processing row {$excelRowNumber}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->results['failed'][] = [
                    'row' => $excelRowNumber,
                    'errors' => [$e->getMessage()],
                    'data' => $row->toArray()
                ];
            }
        }
    }

    private function validateRow(array $row, int $excelRowNumber): array
    {
        // Normalize keys
        $data = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim(str_replace([' ', '_'], '_', $key)));
            $data[$normalizedKey] = $value;
        }

        // 1. Smart Parsing: Check if line_number contains Full Line Number (e.g. 63-PK-KI-LN001)
        // 1. Smart Parsing: Check if line_number contains Full Line Number (e.g. 63-PK-KI-LN001)
        $rawLineNumber = isset($data['line_number']) || isset($data['linenumber'])
            ? (string) ($data['line_number'] ?? $data['linenumber'])
            : null;

        if ($rawLineNumber && preg_match('/^\s*(\d+)\s*-\s*([A-Z0-9\-]+)\s*-\s*LN\s*([A-Za-z0-9]+)\s*$/i', $rawLineNumber, $matches)) {
            // Format detected: {DIAMETER}-{CLUSTER}-LN{SUFFIX}
            $data['diameter'] = $matches[1];
            $data['cluster_code'] = $matches[2];
            $data['line_number_suffix'] = (string) $matches[3]; // Cast to string to satisfy validator
        } else {
            // Fallback: If parsing fails, treat the whole string as suffix (will likely fail max:10 but gives better clue)
            // Or better: don't set it if it looks like a full number but failed regex
            $data['line_number_suffix'] = (string) $rawLineNumber;
        }

        // Extract values (reload after potential parsing)
        $diameter = $data['diameter'] ?? null;
        $clusterCode = $data['cluster_code'] ?? $data['clustercode'] ?? null;
        $lineNumberSuffix = isset($data['line_number_suffix']) ? (string) $data['line_number_suffix'] : null;
        $tanggalJalur = $data['tanggal_jalur'] ?? $data['tanggaljalur'] ?? null;
        $namaJalan = $data['nama_jalan'] ?? $data['namajalan'] ?? null;

        // 2. Shortcodes Map for Tipe Bongkaran
        $tipeBongkaranRaw = $data['tipe_bongkaran'] ?? $data['tipebongkaran'] ?? null;
        $tipeMap = [
            'MB' => 'Manual Boring',
            'OC' => 'Open Cut',
            'CR' => 'Crossing',
            'ZK' => 'Zinker',
            'HDD' => 'HDD',
            'MB-PK' => 'Manual Boring - PK',
            'CR-PK' => 'Crossing - PK'
        ];

        $tipeBongkaran = $tipeMap[strtoupper($tipeBongkaranRaw)] ?? $tipeBongkaranRaw;

        $lowering = $data['lowering'] ?? null;

        // 3. Auto-fill logic
        // Bongkaran defaults to Lowering if empty
        $bongkaran = $data['bongkaran'] ?? null;
        if ((is_null($bongkaran) || $bongkaran === '') && is_numeric($lowering)) {
            $bongkaran = $lowering;
        }

        // Kedalaman defaults to null (handled later)
        $kedalaman = $data['kedalaman'] ?? null;

        $cassingQty = $data['cassing_quantity'] ?? $data['cassingquantity'] ?? null;
        $cassingType = $data['cassing_type'] ?? $data['cassingtype'] ?? null;
        $markerTapeQty = $data['marker_tape_quantity'] ?? $data['markertapequantity'] ?? null;
        $concreteSlabQty = $data['concrete_slab_quantity'] ?? $data['concreteslabquantity'] ?? null;
        $landasanQty = $data['landasan_quantity'] ?? $data['landasanquantity'] ?? null;
        $mc0 = $data['mc_0'] ?? $data['mc0'] ?? null;
        $mc100 = $data['mc_100'] ?? $data['mc100'] ?? null;
        $keterangan = $data['keterangan'] ?? null;

        // Convert Excel date serial number to date string
        if (is_numeric($tanggalJalur) && $tanggalJalur > 0) {
            try {
                $tanggalJalur = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalJalur)->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep original value if conversion fails
                Log::warning("Failed to convert Excel date", ['value' => $tanggalJalur, 'row' => $excelRowNumber]);
            }
        }

        // Convert kedalaman to integer (remove decimals)
        if (!empty($kedalaman)) {
            $kedalaman = (int) round((float) $kedalaman);
        } else {
            $kedalaman = null;
        }

        // Convert concrete_slab_quantity to integer if has value
        if (!empty($concreteSlabQty)) {
            $concreteSlabQty = (int) round((float) $concreteSlabQty);
        }

        // Auto-set cassing_type based on diameter if cassing_quantity has value
        if (!empty($cassingQty) && empty($cassingType)) {
            if ($diameter == 63 || $diameter == 90) {
                $cassingType = '4_inch';
            } elseif ($diameter == 180) {
                $cassingType = '8_inch';
            }
        }

        // Get hyperlinks for this row
        $loweringLink = $this->getHyperlink($excelRowNumber, 'lowering');
        $cassingLink = $this->getHyperlink($excelRowNumber, 'cassing_quantity');
        $markerTapeLink = $this->getHyperlink($excelRowNumber, 'marker_tape_quantity');
        $concreteSlabLink = $this->getHyperlink($excelRowNumber, 'concrete_slab_quantity');
        $landasanLink = $this->getHyperlink($excelRowNumber, 'landasan_quantity');

        // Validation rules
        $rules = [
            'diameter' => 'required|in:63,90,180',
            'cluster_code' => 'required|string|max:10',
            'line_number_suffix' => 'required|string|max:10|regex:/^[0-9A-Za-z]+$/',
            'tanggal_jalur' => 'required|date',
            'nama_jalan' => 'nullable|string|max:255',
            'tipe_bongkaran' => 'required|in:Manual Boring,Open Cut,Crossing,Zinker,HDD,Manual Boring - PK,Crossing - PK',
            'lowering' => 'required|numeric|min:0.01',
            'bongkaran' => 'required|numeric|min:0.01',
            'kedalaman' => 'nullable|integer|min:1',
            'cassing_quantity' => 'nullable|numeric|min:0.1',
            'cassing_type' => 'nullable|in:4_inch,8_inch',
            'marker_tape_quantity' => 'nullable|numeric|min:0.1',
            'concrete_slab_quantity' => 'nullable|integer|min:1',
            'landasan_quantity' => 'nullable|numeric|min:0.1',
            'mc_0' => 'nullable|numeric|min:0.01',
            'mc_100' => 'nullable|numeric|min:0.01',
            'keterangan' => 'nullable|string|max:1000',
        ];

        $validationData = [
            'diameter' => $diameter,
            'cluster_code' => $clusterCode,
            'line_number_suffix' => $lineNumberSuffix,
            'tanggal_jalur' => $tanggalJalur,
            'nama_jalan' => $namaJalan,
            'tipe_bongkaran' => $tipeBongkaran,
            'lowering' => $lowering,
            'bongkaran' => $bongkaran,
            'kedalaman' => $kedalaman,
            'cassing_quantity' => $cassingQty,
            'cassing_type' => $cassingType,
            'marker_tape_quantity' => $markerTapeQty,
            'concrete_slab_quantity' => $concreteSlabQty,
            'landasan_quantity' => $landasanQty,
            'mc_0' => $mc0,
            'mc_100' => $mc100,
            'keterangan' => $keterangan,
        ];

        // Conditional validation: cassing_type required if cassing_quantity has value
        if (!empty($cassingQty)) {
            $rules['cassing_type'] = 'required|in:4_inch,8_inch';
        }

        $validator = Validator::make($validationData, $rules);

        // Custom validation: cluster must exist
        $validator->after(function ($validator) use ($clusterCode) {
            if ($clusterCode) {
                $cluster = JalurCluster::where('code_cluster', $clusterCode)->first();
                if (!$cluster) {
                    $validator->errors()->add('cluster_code', "Cluster dengan code '{$clusterCode}' tidak ditemukan.");
                }
            }
        });

        if ($validator->fails()) {
            return [false, $validationData, $validator->errors()->all()];
        }

        // Add hyperlinks to data
        $validationData['lowering_link'] = $loweringLink;
        $validationData['cassing_link'] = $cassingLink;
        $validationData['marker_tape_link'] = $markerTapeLink;
        $validationData['concrete_slab_link'] = $concreteSlabLink;
        $validationData['landasan_link'] = $landasanLink;

        return [true, $validationData, []];
    }

    private function processRow(array $data, int $excelRowNumber): void
    {
        DB::beginTransaction();

        try {
            // 1. Find or create cluster
            $cluster = JalurCluster::where('code_cluster', $data['cluster_code'])->firstOrFail();

            // 2. Generate full line number
            $fullLineNumber = $data['diameter'] . '-' . $data['cluster_code'] . '-LN' . $data['line_number_suffix'];

            // 3. Find or create line number
            $lineNumber = JalurLineNumber::where('line_number', $fullLineNumber)->first();

            if ($lineNumber) {
                // Check if it belongs to different cluster
                if ($lineNumber->cluster_id != $cluster->id) {
                    throw new \Exception("Line Number {$fullLineNumber} sudah digunakan di cluster {$lineNumber->cluster->nama_cluster}. Gunakan nomor lain.");
                }

                // Prepare update data
                $updateData = [];

                // Update nama_jalan if new value provided (akan timpa nilai lama)
                if (!empty($data['nama_jalan'])) {
                    $updateData['nama_jalan'] = $data['nama_jalan'];
                }

                // Update estimasi_panjang (MC-0) if new value provided (akan timpa nilai lama)
                if (!empty($data['mc_0'])) {
                    $updateData['estimasi_panjang'] = $data['mc_0'];
                }

                // Update actual_mc100 (MC-100) if new value provided (akan timpa nilai lama)
                if (!empty($data['mc_100'])) {
                    $updateData['actual_mc100'] = $data['mc_100'];
                }

                // Execute update if there's data to update
                if (!empty($updateData)) {
                    $lineNumber->update($updateData);
                }
            } else {
                // Create new line number
                $lineNumber = JalurLineNumber::create([
                    'line_number' => $fullLineNumber,
                    'line_code' => 'LN' . $data['line_number_suffix'],
                    'cluster_id' => $cluster->id,
                    'diameter' => (string) $data['diameter'], // Convert to string for ENUM
                    'nama_jalan' => $data['nama_jalan'] ?? null,
                    'estimasi_panjang' => $data['mc_0'] ?? 0, // MC-0 masuk ke estimasi_panjang
                    'actual_mc100' => $data['mc_100'] ?? null,
                    'status_line' => 'draft',
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // 5. Check for existing lowering data (duplicate detection)
            $existingLowering = JalurLoweringData::where('line_number_id', $lineNumber->id)
                ->where('tanggal_jalur', $data['tanggal_jalur'])
                ->where('tipe_bongkaran', $data['tipe_bongkaran'])
                ->first();

            if ($existingLowering) {
                // Record exists - apply Smart Update logic
                $result = $this->smartUpdateLowering($existingLowering, $data, $excelRowNumber);

                if ($result['action'] === 'skipped') {
                    Log::info("Lowering import skipped (no changes or protected)", [
                        'row' => $excelRowNumber,
                        'lowering_id' => $existingLowering->id,
                        'reason' => $result['reason']
                    ]);
                } elseif ($result['action'] === 'updated') {
                    Log::info("Lowering import updated", [
                        'row' => $excelRowNumber,
                        'lowering_id' => $existingLowering->id,
                        'updated_fields' => $result['updated_fields']
                    ]);
                }

                $loweringData = $existingLowering;
            } else {
                // No existing record - create new
                $loweringData = JalurLoweringData::create([
                    'line_number_id' => $lineNumber->id,
                    'nama_jalan' => $lineNumber->nama_jalan,
                    'tanggal_jalur' => $data['tanggal_jalur'],
                    'tipe_bongkaran' => $data['tipe_bongkaran'],
                    'tipe_material' => null,
                    'penggelaran' => $data['lowering'],
                    'bongkaran' => $data['bongkaran'],
                    'kedalaman_lowering' => $data['kedalaman'],
                    'aksesoris_cassing' => !empty($data['cassing_quantity']),
                    'cassing_quantity' => $data['cassing_quantity'],
                    'cassing_type' => $data['cassing_type'],
                    'aksesoris_marker_tape' => !empty($data['marker_tape_quantity']),
                    'marker_tape_quantity' => $data['marker_tape_quantity'],
                    'aksesoris_concrete_slab' => !empty($data['concrete_slab_quantity']),
                    'concrete_slab_quantity' => $data['concrete_slab_quantity'],
                    'aksesoris_landasan' => !empty($data['landasan_quantity']),
                    'landasan_quantity' => $data['landasan_quantity'],
                    'keterangan' => $data['keterangan'],
                    'status_laporan' => 'draft',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // 6. Handle photo uploads from Google Drive links (only for new records)
                if (!empty($data['lowering_link'])) {
                    $this->handlePhotoFromDriveLink($loweringData, 'foto_evidence_penggelaran_bongkaran', $data['lowering_link']);
                }

                if (!empty($data['cassing_quantity']) && !empty($data['cassing_link'])) {
                    $this->handlePhotoFromDriveLink($loweringData, 'foto_evidence_cassing', $data['cassing_link']);
                }

                if (!empty($data['marker_tape_quantity']) && !empty($data['marker_tape_link'])) {
                    $this->handlePhotoFromDriveLink($loweringData, 'foto_evidence_marker_tape', $data['marker_tape_link']);
                }

                if (!empty($data['concrete_slab_quantity']) && !empty($data['concrete_slab_link'])) {
                    $this->handlePhotoFromDriveLink($loweringData, 'foto_evidence_concrete_slab', $data['concrete_slab_link']);
                }

                if (!empty($data['landasan_quantity']) && !empty($data['landasan_link'])) {
                    $this->handlePhotoFromDriveLink($loweringData, 'foto_evidence_landasan', $data['landasan_link']);
                }
            }

            DB::commit();

            Log::info("Successfully imported lowering data", [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'lowering_id' => $loweringData->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function handlePhotoFromDriveLink(JalurLoweringData $lowering, string $fieldName, string $driveLink): void
    {
        try {
            $lineNumber = $lowering->lineNumber->line_number;
            $clusterName = $lowering->lineNumber->cluster->nama_cluster;
            $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
            $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
            $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$tanggalFolder}";

            $result = $this->googleDriveService->copyFromDriveLink(
                $driveLink,
                $customDrivePath,
                $fieldName . '_' . time()
            );

            PhotoApproval::create([
                'reff_id_pelanggan' => null,
                'module_name' => 'jalur_lowering',
                'module_record_id' => $lowering->id,
                'photo_field_name' => $fieldName,
                'photo_url' => $result['url'],
                'drive_file_id' => $result['drive_file_id'] ?? null,
                'photo_status' => 'tracer_pending',
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
            ]);

            Log::info('Photo copied from Google Drive link', [
                'lowering_id' => $lowering->id,
                'field_name' => $fieldName,
                'drive_link' => $driveLink,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to copy photo from Google Drive link', [
                'lowering_id' => $lowering->id,
                'field_name' => $fieldName,
                'drive_link' => $driveLink,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Gagal copy foto {$fieldName} dari Google Drive: " . $e->getMessage());
        }
    }

    /**
     * Smart Update Logic for Lowering Data
     * Only update what changed, protect approved data
     */
    private function smartUpdateLowering($existingLowering, array $newData, int $excelRowNumber): array
    {
        $statusApproved = in_array($existingLowering->status_laporan, ['tracer_approved', 'cgp_approved', 'tracer_rejected', 'cgp_rejected']);

        // Define field categories
        $nonKrusialFields = ['nama_jalan', 'keterangan'];
        $krusialDataFields = ['penggelaran', 'bongkaran', 'kedalaman_lowering', 'cassing_quantity', 'marker_tape_quantity', 'concrete_slab_quantity', 'landasan_quantity'];

        // Check what changed
        $changedFields = [];
        $changedNonKrusial = [];
        $changedKrusial = [];

        // Check non-krusial fields
        if (($existingLowering->nama_jalan ?? '') !== ($newData['nama_jalan'] ?? '')) {
            $changedNonKrusial[] = 'nama_jalan';
        }
        if (($existingLowering->keterangan ?? '') !== ($newData['keterangan'] ?? '')) {
            $changedNonKrusial[] = 'keterangan';
        }

        // Check krusial data fields
        if ((float) $existingLowering->penggelaran !== (float) $newData['lowering']) {
            $changedKrusial[] = 'penggelaran';
        }
        if ((float) $existingLowering->bongkaran !== (float) $newData['bongkaran']) {
            $changedKrusial[] = 'bongkaran';
        }
        if ((int) ($existingLowering->kedalaman_lowering ?? 0) !== (int) ($newData['kedalaman'] ?? 0)) {
            $changedKrusial[] = 'kedalaman_lowering';
        }
        if ((float) ($existingLowering->cassing_quantity ?? 0) !== (float) ($newData['cassing_quantity'] ?? 0)) {
            $changedKrusial[] = 'cassing_quantity';
        }
        if ((float) ($existingLowering->marker_tape_quantity ?? 0) !== (float) ($newData['marker_tape_quantity'] ?? 0)) {
            $changedKrusial[] = 'marker_tape_quantity';
        }
        if ((int) ($existingLowering->concrete_slab_quantity ?? 0) !== (int) ($newData['concrete_slab_quantity'] ?? 0)) {
            $changedKrusial[] = 'concrete_slab_quantity';
        }
        if ((float) ($existingLowering->landasan_quantity ?? 0) !== (float) ($newData['landasan_quantity'] ?? 0)) {
            $changedKrusial[] = 'landasan_quantity';
        }

        // Check photo evidence changes (compare hyperlinks from photo_approvals)
        $photoChanged = $this->checkPhotoLinksChanged($existingLowering, $newData);

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
                if (in_array('nama_jalan', $changedNonKrusial)) {
                    $updateData['nama_jalan'] = $newData['nama_jalan'] ?? null;
                }
                if (in_array('keterangan', $changedNonKrusial)) {
                    $updateData['keterangan'] = $newData['keterangan'] ?? null;
                }

                $existingLowering->update($updateData);

                return [
                    'action' => 'updated',
                    'updated_fields' => $changedNonKrusial,
                    'message' => 'Updated non-krusial fields only (status tetap ' . $existingLowering->status_laporan . ')'
                ];
            }
        } else {
            // Status still draft - can update everything
            $updateData = [
                'penggelaran' => $newData['lowering'],
                'bongkaran' => $newData['bongkaran'],
                'kedalaman_lowering' => $newData['kedalaman'],
                'aksesoris_cassing' => !empty($newData['cassing_quantity']),
                'cassing_quantity' => $newData['cassing_quantity'],
                'cassing_type' => $newData['cassing_type'],
                'aksesoris_marker_tape' => !empty($newData['marker_tape_quantity']),
                'marker_tape_quantity' => $newData['marker_tape_quantity'],
                'aksesoris_concrete_slab' => !empty($newData['concrete_slab_quantity']),
                'concrete_slab_quantity' => $newData['concrete_slab_quantity'],
                'aksesoris_landasan' => !empty($newData['landasan_quantity']),
                'landasan_quantity' => $newData['landasan_quantity'],
                'keterangan' => $newData['keterangan'],
                'nama_jalan' => $newData['nama_jalan'] ?? null,
                'updated_by' => Auth::id(),
            ];

            $existingLowering->update($updateData);

            // Update photos if links changed
            if ($photoChanged) {
                $this->updatePhotosIfChanged($existingLowering, $newData);
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
     * Check if photo links changed by comparing with existing photo_approvals
     */
    private function checkPhotoLinksChanged($lowering, array $newData): bool
    {
        // Get existing photo approvals
        $existingPhotos = \App\Models\PhotoApproval::where('module_name', 'jalur_lowering')
            ->where('module_record_id', $lowering->id)
            ->get()
            ->keyBy('photo_field_name');

        // Check lowering photo
        $loweringPhoto = $existingPhotos->get('foto_evidence_penggelaran_bongkaran');
        if ($loweringPhoto && !empty($newData['lowering_link'])) {
            // Compare drive links
            if ($loweringPhoto->drive_link !== $newData['lowering_link']) {
                return true;
            }
        }

        // Check cassing photo
        $cassingPhoto = $existingPhotos->get('foto_evidence_cassing');
        if ($cassingPhoto && !empty($newData['cassing_link'])) {
            if ($cassingPhoto->drive_link !== $newData['cassing_link']) {
                return true;
            }
        }

        // Check marker tape photo
        $markerPhoto = $existingPhotos->get('foto_evidence_marker_tape');
        if ($markerPhoto && !empty($newData['marker_tape_link'])) {
            if ($markerPhoto->drive_link !== $newData['marker_tape_link']) {
                return true;
            }
        }

        // Check concrete slab photo
        $concretePhoto = $existingPhotos->get('foto_evidence_concrete_slab');
        if ($concretePhoto && !empty($newData['concrete_slab_link'])) {
            if ($concretePhoto->drive_link !== $newData['concrete_slab_link']) {
                return true;
            }
        }

        // Check landasan photo
        $landasanPhoto = $existingPhotos->get('foto_evidence_landasan');
        if ($landasanPhoto && !empty($newData['landasan_link'])) {
            if ($landasanPhoto->drive_link !== $newData['landasan_link']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update photos if links changed (only for draft status)
     */
    private function updatePhotosIfChanged($lowering, array $newData): void
    {
        // Delete old photo approvals
        \App\Models\PhotoApproval::where('module_name', 'jalur_lowering')
            ->where('module_record_id', $lowering->id)
            ->delete();

        // Re-upload with new links
        if (!empty($newData['lowering_link'])) {
            $this->handlePhotoFromDriveLink($lowering, 'foto_evidence_penggelaran_bongkaran', $newData['lowering_link']);
        }

        if (!empty($newData['cassing_quantity']) && !empty($newData['cassing_link'])) {
            $this->handlePhotoFromDriveLink($lowering, 'foto_evidence_cassing', $newData['cassing_link']);
        }

        if (!empty($newData['marker_tape_quantity']) && !empty($newData['marker_tape_link'])) {
            $this->handlePhotoFromDriveLink($lowering, 'foto_evidence_marker_tape', $newData['marker_tape_link']);
        }

        if (!empty($newData['concrete_slab_quantity']) && !empty($newData['concrete_slab_link'])) {
            $this->handlePhotoFromDriveLink($lowering, 'foto_evidence_concrete_slab', $newData['concrete_slab_link']);
        }

        if (!empty($newData['landasan_quantity']) && !empty($newData['landasan_link'])) {
            $this->handlePhotoFromDriveLink($lowering, 'foto_evidence_landasan', $newData['landasan_link']);
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
