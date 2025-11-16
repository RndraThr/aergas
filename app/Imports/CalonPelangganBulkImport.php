<?php

namespace App\Imports;

use App\Models\CalonPelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CalonPelangganBulkImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $updated = 0;
    protected $skipped = 0;
    protected $errors = [];
    protected $allowedColumns = [];
    protected $notFoundReffIds = [];
    protected $missingReffIds = [];
    protected $updatedDetails = [];
    protected $currentRow = 0;
    protected $dryRun = false;
    protected $forceUpdate = false;
    protected $skippedFields = []; // Track fields skipped due to existing values

    public function __construct($dryRun = false, $forceUpdate = false)
    {
        $this->dryRun = $dryRun;
        $this->forceUpdate = $forceUpdate;

        // Get all columns from calon_pelanggan table except timestamps and system columns
        $this->allowedColumns = Schema::getColumnListing('calon_pelanggan');

        // Remove columns that should not be updated via bulk import
        $excludedColumns = [
            'reff_id_pelanggan', // Primary key, used for lookup only
            'created_at',
            'updated_at',
            'validated_at',
            'validated_by',
            'validation_notes',
            'last_login',
            'tanggal_registrasi',
        ];

        $this->allowedColumns = array_diff($this->allowedColumns, $excludedColumns);
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->currentRow++;

        // Get reff_id from the row (required for lookup)
        $reffId = $row['reff_id'] ?? null;

        if (!$reffId) {
            $this->skipped++;
            $this->missingReffIds[] = [
                'row' => $this->currentRow + 1, // +1 for header row
                'reason' => 'Kolom reff_id kosong atau tidak ada'
            ];
            $this->errors[] = "Baris " . ($this->currentRow + 1) . ": reff_id kosong";
            return null;
        }

        // Find customer by reff_id
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();

        if (!$customer) {
            $this->skipped++;
            $this->notFoundReffIds[] = [
                'row' => $this->currentRow + 1,
                'reff_id' => $reffId,
                'reason' => 'Pelanggan tidak ditemukan di database'
            ];
            $this->errors[] = "Baris " . ($this->currentRow + 1) . ": Pelanggan {$reffId} tidak ditemukan";
            return null;
        }

        // Prepare data to update
        $dataToUpdate = [];
        $updatedFields = [];
        $skippedFieldsForRow = []; // Track skipped fields for this row

        // Loop through all columns in the Excel row
        foreach ($row as $columnName => $value) {
            // Skip reff_id as it's only used for lookup
            if ($columnName === 'reff_id') {
                continue;
            }

            // Check if column exists in allowed columns
            if (in_array($columnName, $this->allowedColumns)) {
                // Only update if value is not null/empty
                // Empty values are ignored to preserve existing data
                if ($value !== null && $value !== '') {
                    // Check if force update is disabled and field already has a value
                    if (!$this->forceUpdate && $customer->$columnName !== null && $customer->$columnName !== '') {
                        // Skip this field - it already has a value and force update is not enabled
                        $skippedFieldsForRow[] = [
                            'field' => $columnName,
                            'existing_value' => $customer->$columnName,
                            'excel_value' => $value,
                            'reason' => 'Field sudah memiliki nilai (gunakan Force Update untuk menimpa)'
                        ];
                        continue;
                    }

                    $dataToUpdate[$columnName] = $value;
                    $updatedFields[] = $columnName;
                }
                // Kolom kosong di-skip (tidak diupdate)
            }
        }

        // Update customer if there's data to update
        if (!empty($dataToUpdate)) {
            // Special handling for coordinate updates
            if ((isset($dataToUpdate['latitude']) || isset($dataToUpdate['longitude'])) &&
                ($customer->latitude !== ($dataToUpdate['latitude'] ?? $customer->latitude) ||
                 $customer->longitude !== ($dataToUpdate['longitude'] ?? $customer->longitude))) {
                $dataToUpdate['coordinate_updated_at'] = now();
                if (!isset($dataToUpdate['coordinate_source'])) {
                    $dataToUpdate['coordinate_source'] = 'excel_import';
                }
                $updatedFields[] = 'coordinate_updated_at';
                if (!in_array('coordinate_source', $updatedFields)) {
                    $updatedFields[] = 'coordinate_source';
                }
            }

            // Only update if not in dry run mode
            if (!$this->dryRun) {
                $customer->update($dataToUpdate);
                Log::info("Bulk update for customer: {$reffId}", [
                    'updated_fields' => $updatedFields,
                    'values' => $dataToUpdate
                ]);
            }

            $this->updated++;

            // Track updated details (with old/new values for preview)
            $detailRow = [
                'row' => $this->currentRow + 1,
                'reff_id' => $reffId,
                'nama_pelanggan' => $customer->nama_pelanggan,
                'updated_fields' => implode(', ', $updatedFields),
                'field_count' => count($updatedFields),
                'skipped_fields' => $skippedFieldsForRow,
                'skipped_count' => count($skippedFieldsForRow)
            ];

            // Add old and new values for preview mode
            if ($this->dryRun) {
                $changes = [];
                foreach ($dataToUpdate as $field => $newValue) {
                    $changes[$field] = [
                        'old' => $customer->$field,
                        'new' => $newValue
                    ];
                }
                $detailRow['changes'] = $changes;
            }

            $this->updatedDetails[] = $detailRow;
        } else {
            // Check if there were fields that were skipped due to force_update
            if (!empty($skippedFieldsForRow)) {
                $this->skipped++;
                $this->errors[] = "Baris " . ($this->currentRow + 1) . ": Semua field di-skip karena sudah memiliki nilai (reff_id: {$reffId}). Aktifkan Force Update untuk menimpa.";

                // Still track this for preview
                $this->updatedDetails[] = [
                    'row' => $this->currentRow + 1,
                    'reff_id' => $reffId,
                    'nama_pelanggan' => $customer->nama_pelanggan,
                    'updated_fields' => '',
                    'field_count' => 0,
                    'skipped_fields' => $skippedFieldsForRow,
                    'skipped_count' => count($skippedFieldsForRow),
                    'all_skipped' => true
                ];
            } else {
                $this->skipped++;
                $this->errors[] = "Baris " . ($this->currentRow + 1) . ": Tidak ada data valid untuk diupdate (reff_id: {$reffId})";
            }
        }

        return null; // Return null because we're updating, not creating
    }

    public function rules(): array
    {
        return [
            'reff_id' => 'required',
            'nama_pelanggan' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:1000',
            'no_telepon' => 'nullable|string|max:20',
            'no_bagi' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'kelurahan' => 'nullable|string|max:120',
            'padukuhan' => 'nullable|string|max:120',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'jenis_pelanggan' => 'nullable|in:pengembangan,penetrasi,on_the_spot_penetrasi,on_the_spot_pengembangan',
            'keterangan' => 'nullable|string|max:500',
            'status' => 'nullable|in:pending,lanjut,in_progress,batal',
            'progress_status' => 'nullable|in:validasi,sk,sr,gas_in,done,batal',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'coordinate_source' => 'nullable|in:manual,gps,maps,survey,excel_import',
        ];
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getSkipped()
    {
        return $this->skipped;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getAllowedColumns()
    {
        return $this->allowedColumns;
    }

    public function getNotFoundReffIds()
    {
        return $this->notFoundReffIds;
    }

    public function getMissingReffIds()
    {
        return $this->missingReffIds;
    }

    public function getUpdatedDetails()
    {
        return $this->updatedDetails;
    }

    public function getTotalRows()
    {
        return $this->currentRow;
    }

    public function getForceUpdate()
    {
        return $this->forceUpdate;
    }

    public function getSummary()
    {
        return [
            'total_rows' => $this->currentRow,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'not_found_count' => count($this->notFoundReffIds),
            'missing_reff_count' => count($this->missingReffIds),
            'not_found_reff_ids' => $this->notFoundReffIds,
            'missing_reff_ids' => $this->missingReffIds,
            'updated_details' => $this->updatedDetails,
            'errors' => $this->errors,
            'force_update' => $this->forceUpdate,
        ];
    }
}
