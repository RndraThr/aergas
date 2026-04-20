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
use PhpOffice\PhpSpreadsheet\IOFactory;

class JalurLoweringImport implements ToCollection, WithHeadingRow, WithEvents, WithChunkReading
{
    private const APPROVED_STATUSES = ['acc_tracer', 'acc_cgp'];
    private const NON_KRUSIAL_FIELDS = ['nama_jalan', 'keterangan'];
    private const KRUSIAL_FIELDS = [
        'cassing_quantity', 'cassing_type',
        'marker_tape_quantity', 'concrete_slab_quantity', 'landasan_quantity',
    ];

    private bool $dryRun;
    private bool $forceUpdate;
    private bool $allowRecall;
    private string $filePath;
    private GoogleDriveService $googleDriveService;

    private array $hyperlinks = [];
    private array $rowMapping = [];
    private array $seenKeys = [];

    private array $results = [
        'new' => [],
        'update' => [],
        'skip_no_change' => [],
        'skip_approved' => [],
        'recall' => [],
        'error' => [],
        'duplicate_in_file' => [],
        'empty' => 0,
    ];

    public function __construct(
        bool $dryRun = false,
        string $filePath = '',
        bool $forceUpdate = false,
        bool $allowRecall = false
    ) {
        $this->dryRun = $dryRun;
        $this->filePath = $filePath;
        $this->forceUpdate = $forceUpdate;
        $this->allowRecall = $allowRecall;
        $this->googleDriveService = app(GoogleDriveService::class);
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function () {
                if (!empty($this->filePath) && file_exists($this->filePath)) {
                    $this->extractAllHyperlinks();
                }
            },
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getSummary(): array
    {
        return [
            'total_rows' => count($this->results['new'])
                + count($this->results['update'])
                + count($this->results['skip_no_change'])
                + count($this->results['skip_approved'])
                + count($this->results['recall'])
                + count($this->results['error'])
                + count($this->results['duplicate_in_file']),
            'new' => count($this->results['new']),
            'update' => count($this->results['update']),
            'skip_no_change' => count($this->results['skip_no_change']),
            'skip_approved' => count($this->results['skip_approved']),
            'recall' => count($this->results['recall']),
            'error' => count($this->results['error']),
            'duplicate_in_file' => count($this->results['duplicate_in_file']),
            'empty' => $this->results['empty'],
            'force_update' => $this->forceUpdate,
            'allow_recall' => $this->allowRecall,
            'details' => $this->results,
        ];
    }

    private function extractAllHyperlinks(): void
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 2; $row <= $highestRow; $row++) {
                $lineNumber = $worksheet->getCell('C' . $row)->getValue();
                $tanggal = $worksheet->getCell('D' . $row)->getValue();
                $tipeBongkaran = $worksheet->getCell('F' . $row)->getValue();
                $lowering = $worksheet->getCell('G' . $row)->getValue();
                $bongkaran = $worksheet->getCell('H' . $row)->getValue();
                $kedalaman = $worksheet->getCell('I' . $row)->getValue();

                if ($tanggal && is_numeric($tanggal)) {
                    try {
                        $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // keep original
                    }
                }

                if ($lineNumber && $tanggal && $tipeBongkaran) {
                    $key = implode('|', [
                        $lineNumber,
                        $tanggal,
                        $tipeBongkaran,
                        (float) ($lowering ?? 0),
                        (float) ($bongkaran ?? 0),
                        $kedalaman === null || $kedalaman === '' ? '' : (int) round((float) $kedalaman),
                    ]);
                    $this->rowMapping[$key] = $row;
                }

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellCoordinate);

                    if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $this->hyperlinks[$row][$columnLetter] = $cell->getHyperlink()->getUrl();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to extract hyperlinks", ['file' => $this->filePath, 'error' => $e->getMessage()]);
        }
    }

    private function getHyperlink(int $excelRow, string $columnName): ?string
    {
        $columnMap = [
            'lowering' => 'G',
            'bongkaran' => 'H',
            'cassing_quantity' => 'J',
            'marker_tape_quantity' => 'K',
            'concrete_slab_quantity' => 'L',
            'landasan_quantity' => 'M',
            'mc_0' => 'N',
            'mc_100' => 'O',
        ];

        $letter = $columnMap[$columnName] ?? null;
        if (!$letter) {
            return null;
        }

        return $this->hyperlinks[$excelRow][$letter] ?? null;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArr = $row->toArray();

            if (empty(array_filter($rowArr, fn($v) => $v !== null && $v !== ''))) {
                $this->results['empty']++;
                continue;
            }

            $lineNumber = $rowArr['line_number'] ?? null;
            $tanggal = $rowArr['tanggal_jalur'] ?? null;
            $tipeBongkaranRaw = $rowArr['tipe_bongkaran'] ?? null;
            $loweringRaw = $rowArr['lowering'] ?? null;
            $bongkaranRaw = $rowArr['bongkaran'] ?? null;
            $kedalamanRaw = $rowArr['kedalaman'] ?? null;
            $excelRowNumber = $index + 2;

            if ($lineNumber && $tanggal && $tipeBongkaranRaw) {
                if (is_numeric($tanggal)) {
                    try {
                        $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // keep original
                    }
                }
                $mappingKey = implode('|', [
                    $lineNumber,
                    $tanggal,
                    $tipeBongkaranRaw,
                    (float) ($loweringRaw ?? 0),
                    (float) ($bongkaranRaw ?? 0),
                    $kedalamanRaw === null || $kedalamanRaw === '' ? '' : (int) round((float) $kedalamanRaw),
                ]);
                if (isset($this->rowMapping[$mappingKey])) {
                    $excelRowNumber = $this->rowMapping[$mappingKey];
                }
            }

            try {
                [$isValid, $data, $errors] = $this->validateRow($rowArr, $excelRowNumber);

                if (!$isValid) {
                    $this->results['error'][] = [
                        'row' => $excelRowNumber,
                        'line_number' => $lineNumber,
                        'tanggal' => $tanggal,
                        'errors' => $errors,
                        'raw' => $rowArr,
                    ];
                    continue;
                }

                $this->classifyAndProcess($data, $excelRowNumber);
            } catch (\Exception $e) {
                Log::error("Error processing row {$excelRowNumber}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->results['error'][] = [
                    'row' => $excelRowNumber,
                    'line_number' => $lineNumber,
                    'tanggal' => $tanggal,
                    'errors' => [$e->getMessage()],
                    'raw' => $rowArr,
                ];
            }
        }
    }

    private function validateRow(array $row, int $excelRowNumber): array
    {
        $data = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim(str_replace([' ', '_'], '_', $key)));
            $data[$normalizedKey] = $value;
        }

        $rawLineNumber = isset($data['line_number']) || isset($data['linenumber'])
            ? (string) ($data['line_number'] ?? $data['linenumber'])
            : null;

        if ($rawLineNumber && preg_match('/^\s*(\d+)\s*-\s*([A-Z0-9\-]+)\s*-\s*LN\s*([A-Za-z0-9]+)\s*$/i', $rawLineNumber, $matches)) {
            $data['diameter'] = $matches[1];
            $data['cluster_code'] = $matches[2];
            $data['line_number_suffix'] = (string) $matches[3];
        } else {
            $data['line_number_suffix'] = (string) $rawLineNumber;
        }

        $diameter = $data['diameter'] ?? null;
        $clusterCode = $data['cluster_code'] ?? $data['clustercode'] ?? null;
        $lineNumberSuffix = isset($data['line_number_suffix']) ? (string) $data['line_number_suffix'] : null;
        $tanggalJalur = $data['tanggal_jalur'] ?? $data['tanggaljalur'] ?? null;
        $namaJalan = $data['nama_jalan'] ?? $data['namajalan'] ?? null;

        $tipeBongkaranRaw = $data['tipe_bongkaran'] ?? $data['tipebongkaran'] ?? null;
        $tipeMap = [
            'MB' => 'Manual Boring',
            'OC' => 'Open Cut',
            'CR' => 'Crossing',
            'ZK' => 'Zinker',
            'HDD' => 'HDD',
            'MB-PK' => 'Manual Boring - PK',
            'CR-PK' => 'Crossing - PK',
        ];
        $tipeBongkaran = $tipeMap[strtoupper((string) $tipeBongkaranRaw)] ?? $tipeBongkaranRaw;

        $lowering = $data['lowering'] ?? null;
        $bongkaran = $data['bongkaran'] ?? null;
        if ((is_null($bongkaran) || $bongkaran === '') && is_numeric($lowering)) {
            $bongkaran = $lowering;
        }

        $kedalaman = $data['kedalaman'] ?? null;
        $cassingQty = $data['cassing_quantity'] ?? $data['cassingquantity'] ?? null;
        $cassingType = $data['cassing_type'] ?? $data['cassingtype'] ?? null;
        $markerTapeQty = $data['marker_tape_quantity'] ?? $data['markertapequantity'] ?? null;
        $concreteSlabQty = $data['concrete_slab_quantity'] ?? $data['concreteslabquantity'] ?? null;
        $landasanQty = $data['landasan_quantity'] ?? $data['landasanquantity'] ?? null;
        $mc0 = $data['mc_0'] ?? $data['mc0'] ?? null;
        $mc100 = $data['mc_100'] ?? $data['mc100'] ?? null;
        $keterangan = $data['keterangan'] ?? null;

        if (is_numeric($tanggalJalur) && $tanggalJalur > 0) {
            try {
                $tanggalJalur = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalJalur)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Failed to convert Excel date", ['value' => $tanggalJalur, 'row' => $excelRowNumber]);
            }
        }

        if (!empty($kedalaman)) {
            $kedalaman = (int) round((float) $kedalaman);
        } else {
            $kedalaman = null;
        }

        if (!empty($concreteSlabQty)) {
            $concreteSlabQty = (int) round((float) $concreteSlabQty);
        }

        if (!empty($cassingQty) && empty($cassingType)) {
            if ($diameter == 63 || $diameter == 90) {
                $cassingType = '4_inch';
            } elseif ($diameter == 180) {
                $cassingType = '8_inch';
            }
        }

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

        if (!empty($cassingQty)) {
            $rules['cassing_type'] = 'required|in:4_inch,8_inch';
        }

        $validator = Validator::make($validationData, $rules);
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

        $validationData['lowering_link'] = $this->getHyperlink($excelRowNumber, 'lowering');
        $validationData['cassing_link'] = $this->getHyperlink($excelRowNumber, 'cassing_quantity');
        $validationData['marker_tape_link'] = $this->getHyperlink($excelRowNumber, 'marker_tape_quantity');
        $validationData['concrete_slab_link'] = $this->getHyperlink($excelRowNumber, 'concrete_slab_quantity');
        $validationData['landasan_link'] = $this->getHyperlink($excelRowNumber, 'landasan_quantity');

        return [true, $validationData, []];
    }

    private function classifyAndProcess(array $data, int $excelRowNumber): void
    {
        $fullLineNumber = $data['diameter'] . '-' . $data['cluster_code'] . '-LN' . $data['line_number_suffix'];
        $kedalamanKey = $data['kedalaman'] ?? '';
        $dedupKey = implode('|', [
            $fullLineNumber,
            $data['tanggal_jalur'],
            $data['tipe_bongkaran'],
            (float) $data['lowering'],
            (float) $data['bongkaran'],
            $kedalamanKey,
        ]);

        if (isset($this->seenKeys[$dedupKey])) {
            $this->results['duplicate_in_file'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'tipe_bongkaran' => $data['tipe_bongkaran'],
                'lowering' => $data['lowering'],
                'bongkaran' => $data['bongkaran'],
                'kedalaman' => $data['kedalaman'],
                'first_seen_row' => $this->seenKeys[$dedupKey],
                'message' => "Kombinasi 6 field sama dengan baris {$this->seenKeys[$dedupKey]} di file ini",
            ];
            return;
        }
        $this->seenKeys[$dedupKey] = $excelRowNumber;

        $cluster = JalurCluster::where('code_cluster', $data['cluster_code'])->first();
        if (!$cluster) {
            $this->results['error'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'errors' => ["Cluster {$data['cluster_code']} tidak ditemukan"],
                'raw' => $data,
            ];
            return;
        }

        $lineNumber = JalurLineNumber::where('line_number', $fullLineNumber)->first();
        if ($lineNumber && $lineNumber->cluster_id != $cluster->id) {
            $this->results['error'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'errors' => ["Line Number {$fullLineNumber} sudah digunakan di cluster lain"],
                'raw' => $data,
            ];
            return;
        }

        $existing = null;
        if ($lineNumber) {
            $query = JalurLoweringData::where('line_number_id', $lineNumber->id)
                ->where('tanggal_jalur', $data['tanggal_jalur'])
                ->where('tipe_bongkaran', $data['tipe_bongkaran'])
                ->where('penggelaran', $data['lowering'])
                ->where('bongkaran', $data['bongkaran']);

            if ($data['kedalaman'] === null) {
                $query->whereNull('kedalaman_lowering');
            } else {
                $query->where('kedalaman_lowering', $data['kedalaman']);
            }

            $existing = $query->first();
        }

        if (!$existing) {
            $this->results['new'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'tipe_bongkaran' => $data['tipe_bongkaran'],
                'data' => $this->buildPreviewData($data),
            ];

            if (!$this->dryRun) {
                $this->commitNew($data, $cluster, $lineNumber, $fullLineNumber);
            }
            return;
        }

        $this->classifyExisting($existing, $data, $excelRowNumber, $fullLineNumber, $lineNumber, $cluster);
    }

    private function classifyExisting(
        JalurLoweringData $existing,
        array $data,
        int $excelRowNumber,
        string $fullLineNumber,
        JalurLineNumber $lineNumber,
        JalurCluster $cluster
    ): void {
        $isApproved = in_array($existing->status_laporan, self::APPROVED_STATUSES);
        $diff = $this->computeDiff($existing, $data);

        if (empty($diff['krusial']) && empty($diff['non_krusial']) && empty($diff['photos'])) {
            $this->results['skip_no_change'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'tipe_bongkaran' => $data['tipe_bongkaran'],
                'existing_id' => $existing->id,
                'status' => $existing->status_laporan,
                'message' => 'Tidak ada perubahan',
            ];
            return;
        }

        Log::info('Lowering import diff detected', [
            'row' => $excelRowNumber,
            'existing_id' => $existing->id,
            'line_number' => $fullLineNumber,
            'tanggal' => $data['tanggal_jalur'],
            'tipe_bongkaran' => $data['tipe_bongkaran'],
            'non_krusial' => array_map(fn($e) => [
                'field' => $e['field'],
                'old' => $e['old'],
                'old_type' => gettype($e['old']),
                'new' => $e['new'],
                'new_type' => gettype($e['new']),
                'will_apply' => $e['will_apply'],
            ], $diff['non_krusial']),
            'krusial' => array_map(fn($e) => [
                'field' => $e['field'],
                'old' => $e['old'],
                'old_type' => gettype($e['old']),
                'new' => $e['new'],
                'new_type' => gettype($e['new']),
                'will_apply' => $e['will_apply'],
            ], $diff['krusial']),
            'photos' => array_map(fn($e) => [
                'field' => $e['field'],
                'old' => $e['old'],
                'new' => $e['new'],
                'will_apply' => $e['will_apply'],
            ], $diff['photos']),
        ]);

        if ($isApproved) {
            $hasKrusialOrPhoto = !empty($diff['krusial']) || !empty($diff['photos']);

            if ($hasKrusialOrPhoto && !$this->allowRecall) {
                $this->results['skip_approved'][] = [
                    'row' => $excelRowNumber,
                    'line_number' => $fullLineNumber,
                    'tanggal' => $data['tanggal_jalur'],
                    'tipe_bongkaran' => $data['tipe_bongkaran'],
                    'existing_id' => $existing->id,
                    'status' => $existing->status_laporan,
                    'diff' => $diff,
                    'message' => 'Record sudah approved — dilindungi (aktifkan Allow Recall untuk force)',
                ];
                return;
            }

            if ($hasKrusialOrPhoto && $this->allowRecall) {
                $this->results['recall'][] = [
                    'row' => $excelRowNumber,
                    'line_number' => $fullLineNumber,
                    'tanggal' => $data['tanggal_jalur'],
                    'tipe_bongkaran' => $data['tipe_bongkaran'],
                    'existing_id' => $existing->id,
                    'status' => $existing->status_laporan,
                    'diff' => $diff,
                    'message' => 'Akan di-recall ke draft & overwrite — butuh re-approval',
                ];

                if (!$this->dryRun) {
                    $this->commitRecall($existing, $data, $lineNumber);
                }
                return;
            }

            $this->results['update'][] = [
                'row' => $excelRowNumber,
                'line_number' => $fullLineNumber,
                'tanggal' => $data['tanggal_jalur'],
                'tipe_bongkaran' => $data['tipe_bongkaran'],
                'existing_id' => $existing->id,
                'status' => $existing->status_laporan,
                'diff' => $diff,
                'message' => 'Hanya update non-krusial (status approved tetap)',
            ];

            if (!$this->dryRun) {
                $this->commitUpdate($existing, $data, $diff, onlyNonKrusial: true);
            }
            return;
        }

        $this->results['update'][] = [
            'row' => $excelRowNumber,
            'line_number' => $fullLineNumber,
            'tanggal' => $data['tanggal_jalur'],
            'tipe_bongkaran' => $data['tipe_bongkaran'],
            'existing_id' => $existing->id,
            'status' => $existing->status_laporan,
            'diff' => $diff,
            'message' => $this->forceUpdate ? 'Force update (timpa semua)' : 'Update (isi field kosong)',
        ];

        if (!$this->dryRun) {
            $this->commitUpdate($existing, $data, $diff, onlyNonKrusial: false);
        }
    }

    private function computeDiff(JalurLoweringData $existing, array $data): array
    {
        $diff = ['non_krusial' => [], 'krusial' => [], 'photos' => []];

        $fieldMap = [
            'nama_jalan' => $data['nama_jalan'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'cassing_quantity' => $data['cassing_quantity'] ?? null,
            'cassing_type' => $data['cassing_type'] ?? null,
            'marker_tape_quantity' => $data['marker_tape_quantity'] ?? null,
            'concrete_slab_quantity' => $data['concrete_slab_quantity'] ?? null,
            'landasan_quantity' => $data['landasan_quantity'] ?? null,
        ];

        foreach ($fieldMap as $dbField => $newValue) {
            $oldValue = $existing->{$dbField};

            if ($this->valuesEqual($oldValue, $newValue, $dbField)) {
                continue;
            }

            $oldIsEmpty = $oldValue === null || $oldValue === '' || (is_numeric($oldValue) && (float) $oldValue == 0.0);
            $willApply = $this->forceUpdate || $oldIsEmpty;

            $entry = [
                'field' => $dbField,
                'old' => $oldValue,
                'new' => $newValue,
                'will_apply' => $willApply,
            ];

            if (in_array($dbField, self::NON_KRUSIAL_FIELDS)) {
                $diff['non_krusial'][] = $entry;
            } else {
                $diff['krusial'][] = $entry;
            }
        }

        $diff['photos'] = $this->computePhotoDiff($existing, $data);

        return $diff;
    }

    private function valuesEqual($old, $new, string $field): bool
    {
        if ($field === 'concrete_slab_quantity') {
            return (int) ($old ?? 0) === (int) ($new ?? 0);
        }
        if (in_array($field, ['cassing_quantity', 'marker_tape_quantity', 'landasan_quantity'], true)) {
            return (float) ($old ?? 0) === (float) ($new ?? 0);
        }
        return ((string) ($old ?? '')) === ((string) ($new ?? ''));
    }

    private function computePhotoDiff(JalurLoweringData $existing, array $data): array
    {
        $existingPhotos = PhotoApproval::where('module_name', 'jalur_lowering')
            ->where('module_record_id', $existing->id)
            ->get()
            ->keyBy('photo_field_name');

        $photoMap = [
            'foto_evidence_penggelaran_bongkaran' => [
                'link' => $data['lowering_link'] ?? null,
                'guard' => true,
            ],
            'foto_evidence_cassing' => [
                'link' => $data['cassing_link'] ?? null,
                'guard' => !empty($data['cassing_quantity']),
            ],
            'foto_evidence_marker_tape' => [
                'link' => $data['marker_tape_link'] ?? null,
                'guard' => !empty($data['marker_tape_quantity']),
            ],
            'foto_evidence_concrete_slab' => [
                'link' => $data['concrete_slab_link'] ?? null,
                'guard' => !empty($data['concrete_slab_quantity']),
            ],
            'foto_evidence_landasan' => [
                'link' => $data['landasan_link'] ?? null,
                'guard' => !empty($data['landasan_quantity']),
            ],
        ];

        $changes = [];
        foreach ($photoMap as $fieldName => $cfg) {
            if (!$cfg['guard'] || empty($cfg['link'])) {
                continue;
            }

            $existingPhoto = $existingPhotos->get($fieldName);

            if (!$existingPhoto) {
                $changes[] = [
                    'field' => $fieldName,
                    'old' => null,
                    'new' => $cfg['link'],
                    'will_apply' => true,
                ];
                continue;
            }

            $oldLink = $existingPhoto->drive_link;

            if (empty($oldLink)) {
                continue;
            }

            if ($oldLink === $cfg['link']) {
                continue;
            }

            $changes[] = [
                'field' => $fieldName,
                'old' => $oldLink,
                'new' => $cfg['link'],
                'will_apply' => $this->forceUpdate,
            ];
        }

        return $changes;
    }

    private function buildPreviewData(array $data): array
    {
        return [
            'nama_jalan' => $data['nama_jalan'] ?? null,
            'penggelaran' => $data['lowering'] ?? null,
            'bongkaran' => $data['bongkaran'] ?? null,
            'kedalaman_lowering' => $data['kedalaman'] ?? null,
            'cassing_quantity' => $data['cassing_quantity'] ?? null,
            'cassing_type' => $data['cassing_type'] ?? null,
            'marker_tape_quantity' => $data['marker_tape_quantity'] ?? null,
            'concrete_slab_quantity' => $data['concrete_slab_quantity'] ?? null,
            'landasan_quantity' => $data['landasan_quantity'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'has_photos' => !empty($data['lowering_link'])
                || !empty($data['cassing_link'])
                || !empty($data['marker_tape_link'])
                || !empty($data['concrete_slab_link'])
                || !empty($data['landasan_link']),
        ];
    }

    private function commitNew(array $data, JalurCluster $cluster, ?JalurLineNumber $lineNumber, string $fullLineNumber): void
    {
        DB::beginTransaction();
        try {
            if (!$lineNumber) {
                $lineNumber = JalurLineNumber::create([
                    'line_number' => $fullLineNumber,
                    'line_code' => 'LN' . $data['line_number_suffix'],
                    'cluster_id' => $cluster->id,
                    'diameter' => (string) $data['diameter'],
                    'nama_jalan' => $data['nama_jalan'] ?? null,
                    'estimasi_panjang' => $data['mc_0'] ?? 0,
                    'actual_mc100' => $data['mc_100'] ?? null,
                    'status_line' => 'draft',
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            } else {
                $updateData = [];
                if (!empty($data['nama_jalan'])) {
                    $updateData['nama_jalan'] = $data['nama_jalan'];
                }
                if (!empty($data['mc_0'])) {
                    $updateData['estimasi_panjang'] = $data['mc_0'];
                }
                if (!empty($data['mc_100'])) {
                    $updateData['actual_mc100'] = $data['mc_100'];
                }
                if (!empty($updateData)) {
                    $lineNumber->update($updateData);
                }
            }

            $lowering = JalurLoweringData::create([
                'line_number_id' => $lineNumber->id,
                'nama_jalan' => $data['nama_jalan'] ?? $lineNumber->nama_jalan,
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

            $this->uploadPhotosForNew($lowering, $data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function commitUpdate(JalurLoweringData $existing, array $data, array $diff, bool $onlyNonKrusial): void
    {
        DB::beginTransaction();
        try {
            $updateData = [];
            $applyList = $onlyNonKrusial
                ? array_filter($diff['non_krusial'], fn($e) => $e['will_apply'])
                : array_merge(
                    array_filter($diff['non_krusial'], fn($e) => $e['will_apply']),
                    array_filter($diff['krusial'], fn($e) => $e['will_apply'])
                );

            foreach ($applyList as $entry) {
                $updateData[$entry['field']] = $entry['new'];

                if ($entry['field'] === 'cassing_quantity') {
                    $updateData['aksesoris_cassing'] = !empty($entry['new']);
                }
                if ($entry['field'] === 'marker_tape_quantity') {
                    $updateData['aksesoris_marker_tape'] = !empty($entry['new']);
                }
                if ($entry['field'] === 'concrete_slab_quantity') {
                    $updateData['aksesoris_concrete_slab'] = !empty($entry['new']);
                }
                if ($entry['field'] === 'landasan_quantity') {
                    $updateData['aksesoris_landasan'] = !empty($entry['new']);
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = Auth::id();
                $existing->update($updateData);
            }

            if (!$onlyNonKrusial) {
                $this->applyPhotoChanges($existing, $diff['photos']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function commitRecall(JalurLoweringData $existing, array $data, JalurLineNumber $lineNumber): void
    {
        DB::beginTransaction();
        try {
            $updateData = [
                'status_laporan' => 'draft',
                'tracer_approved_at' => null,
                'tracer_approved_by' => null,
                'tracer_notes' => null,
                'cgp_approved_at' => null,
                'cgp_approved_by' => null,
                'cgp_notes' => null,
                'nama_jalan' => $data['nama_jalan'] ?? $existing->nama_jalan,
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
                'updated_by' => Auth::id(),
            ];

            $existing->update($updateData);

            PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $existing->id)
                ->update([
                    'photo_status' => 'tracer_pending',
                    'tracer_approved_at' => null,
                    'tracer_user_id' => null,
                    'tracer_rejected_at' => null,
                    'cgp_approved_at' => null,
                    'cgp_user_id' => null,
                    'cgp_rejected_at' => null,
                ]);

            $this->replacePhotosWithNewLinks($existing, $data);

            Log::info('Lowering record recalled from approval via import', [
                'lowering_id' => $existing->id,
                'previous_status' => $existing->getOriginal('status_laporan'),
                'user_id' => Auth::id(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function uploadPhotosForNew(JalurLoweringData $lowering, array $data): void
    {
        $map = [
            'foto_evidence_penggelaran_bongkaran' => ['link' => $data['lowering_link'] ?? null, 'guard' => true],
            'foto_evidence_cassing' => ['link' => $data['cassing_link'] ?? null, 'guard' => !empty($data['cassing_quantity'])],
            'foto_evidence_marker_tape' => ['link' => $data['marker_tape_link'] ?? null, 'guard' => !empty($data['marker_tape_quantity'])],
            'foto_evidence_concrete_slab' => ['link' => $data['concrete_slab_link'] ?? null, 'guard' => !empty($data['concrete_slab_quantity'])],
            'foto_evidence_landasan' => ['link' => $data['landasan_link'] ?? null, 'guard' => !empty($data['landasan_quantity'])],
        ];

        foreach ($map as $fieldName => $cfg) {
            if ($cfg['guard'] && !empty($cfg['link'])) {
                $this->uploadPhotoFromDriveLink($lowering, $fieldName, $cfg['link']);
            }
        }
    }

    private function applyPhotoChanges(JalurLoweringData $lowering, array $photoDiff): void
    {
        foreach ($photoDiff as $change) {
            if (!$change['will_apply']) {
                continue;
            }

            PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->where('photo_field_name', $change['field'])
                ->delete();

            $this->uploadPhotoFromDriveLink($lowering, $change['field'], $change['new']);
        }
    }

    private function replacePhotosWithNewLinks(JalurLoweringData $lowering, array $data): void
    {
        $map = [
            'foto_evidence_penggelaran_bongkaran' => $data['lowering_link'] ?? null,
            'foto_evidence_cassing' => !empty($data['cassing_quantity']) ? ($data['cassing_link'] ?? null) : null,
            'foto_evidence_marker_tape' => !empty($data['marker_tape_quantity']) ? ($data['marker_tape_link'] ?? null) : null,
            'foto_evidence_concrete_slab' => !empty($data['concrete_slab_quantity']) ? ($data['concrete_slab_link'] ?? null) : null,
            'foto_evidence_landasan' => !empty($data['landasan_quantity']) ? ($data['landasan_link'] ?? null) : null,
        ];

        foreach ($map as $fieldName => $newLink) {
            if (empty($newLink)) {
                continue;
            }

            $existing = PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->where('photo_field_name', $fieldName)
                ->first();

            if ($existing && $existing->drive_link === $newLink) {
                continue;
            }

            if ($existing) {
                $existing->delete();
            }

            $this->uploadPhotoFromDriveLink($lowering, $fieldName, $newLink);
        }
    }

    private function uploadPhotoFromDriveLink(JalurLoweringData $lowering, string $fieldName, string $driveLink): void
    {
        try {
            $lineNumberStr = $lowering->lineNumber->line_number;
            $clusterName = $lowering->lineNumber->cluster->nama_cluster;
            $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
            $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
            $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumberStr}/{$tanggalFolder}";

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
                'drive_link' => $driveLink,
                'photo_status' => 'tracer_pending',
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to copy photo from Google Drive link', [
                'lowering_id' => $lowering->id,
                'field_name' => $fieldName,
                'drive_link' => $driveLink,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Gagal copy foto {$fieldName} dari Google Drive: " . $e->getMessage());
        }
    }
}
