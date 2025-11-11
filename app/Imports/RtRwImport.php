<?php

namespace App\Imports;

use App\Models\CalonPelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class RtRwImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $updated = 0;
    protected $skipped = 0;
    protected $errors = [];

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Get reff_id from the row
        $reffId = $row['reff_id'] ?? null;

        if (!$reffId) {
            $this->skipped++;
            $this->errors[] = "Row skipped: missing reff_id";
            return null;
        }

        // Find customer by reff_id
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();

        if (!$customer) {
            $this->skipped++;
            $this->errors[] = "Customer not found: {$reffId}";
            return null;
        }

        // Update RT and RW
        $customer->rt = $row['rt'] ?? null;
        $customer->rw = $row['rw'] ?? null;
        $customer->save();

        $this->updated++;

        Log::info("RT/RW updated for customer: {$reffId}", [
            'rt' => $customer->rt,
            'rw' => $customer->rw
        ]);

        return null; // Return null because we're updating, not creating
    }

    public function rules(): array
    {
        return [
            'reff_id' => 'nullable',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
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
}
