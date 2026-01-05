<?php

namespace App\Imports;

use App\Models\JalurCluster;
use App\Models\JalurLineNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class JalurMc0Import implements ToCollection, WithHeadingRow, WithChunkReading
{
    private bool $dryRun;
    private array $results = ['success' => 0, 'updated' => 0, 'created' => 0, 'skipped' => 0, 'failed' => []];

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $excelRowNumber = $index + 2; // Excel row (header is row 1)

            // Skip empty rows
            if (empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== ''))) {
                $this->results['skipped']++;
                continue;
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
                    // Dry run mode - just validate without updating
                    $this->results['success']++;
                    continue;
                }

                // Process and update data
                $updated = $this->processRow($data, $excelRowNumber);

                if ($updated) {
                    $this->results['updated']++;
                } else {
                    $this->results['created']++;
                }

                $this->results['success']++;

            } catch (\Exception $e) {
                Log::error("Error processing MC-0 row {$excelRowNumber}", [
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

        // Extract values
        $diameter = $data['diameter'] ?? null;
        $clusterCode = $data['cluster_code'] ?? $data['clustercode'] ?? null;
        $lineNumberSuffix = $data['line_number'] ?? $data['linenumber'] ?? null;
        $mc0 = $data['mc_0'] ?? $data['mc0'] ?? null;

        // Validation rules
        $rules = [
            'diameter' => 'required|in:63,90,180',
            'cluster_code' => 'required|string|max:10',
            'line_number_suffix' => 'required|string|max:10|regex:/^[0-9A-Za-z]+$/',
            'mc_0' => 'required|numeric|min:0.01',
        ];

        $validationData = [
            'diameter' => $diameter,
            'cluster_code' => $clusterCode,
            'line_number_suffix' => $lineNumberSuffix,
            'mc_0' => $mc0,
        ];

        $validator = Validator::make($validationData, $rules);

        // Custom validation: cluster must exist
        $validator->after(function ($validator) use ($clusterCode) {
            $cluster = JalurCluster::where('code_cluster', $clusterCode)->first();
            if (!$cluster) {
                $validator->errors()->add('cluster_code', "Cluster dengan code '{$clusterCode}' tidak ditemukan.");
            }
        });

        if ($validator->fails()) {
            return [false, $validationData, $validator->errors()->all()];
        }

        return [true, $validationData, []];
    }

    private function processRow(array $data, int $excelRowNumber): bool
    {
        DB::beginTransaction();

        try {
            // 1. Find cluster
            $cluster = JalurCluster::where('code_cluster', $data['cluster_code'])->firstOrFail();

            // 2. Generate full line number
            $fullLineNumber = $data['diameter'] . '-' . $data['cluster_code'] . '-LN' . $data['line_number_suffix'];

            // 3. Find or create line number
            $lineNumber = JalurLineNumber::where('line_number', $fullLineNumber)->first();

            $isUpdate = false;

            if ($lineNumber) {
                // Check if it belongs to different cluster
                if ($lineNumber->cluster_id != $cluster->id) {
                    throw new \Exception("Line Number {$fullLineNumber} sudah digunakan di cluster {$lineNumber->cluster->nama_cluster}. Gunakan nomor lain.");
                }

                // Update existing line number
                $lineNumber->update([
                    'estimasi_panjang' => $data['mc_0'],
                    'updated_by' => Auth::id(),
                ]);

                $isUpdate = true;

                Log::info("MC-0 updated for existing line number", [
                    'row' => $excelRowNumber,
                    'line_number' => $fullLineNumber,
                    'mc_0' => $data['mc_0']
                ]);

            } else {
                // Create new line number with MC-0
                $lineNumber = JalurLineNumber::create([
                    'line_number' => $fullLineNumber,
                    'line_code' => 'LN' . $data['line_number_suffix'],
                    'cluster_id' => $cluster->id,
                    'diameter' => (string) $data['diameter'],
                    'estimasi_panjang' => $data['mc_0'],
                    'actual_mc100' => null,
                    'status_line' => 'draft',
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                Log::info("New line number created with MC-0", [
                    'row' => $excelRowNumber,
                    'line_number' => $fullLineNumber,
                    'mc_0' => $data['mc_0']
                ]);
            }

            DB::commit();

            return $isUpdate;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
