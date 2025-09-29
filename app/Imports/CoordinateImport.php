<?php

namespace App\Imports;

use App\Models\CalonPelanggan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CoordinateImport implements ToCollection, WithHeadingRow
{
    private bool $dryRun;
    private int $headingRow;
    private int $chunk = 500;

    private array $buf = [];

    protected array $results = [
        'success' => 0,
        'updated' => 0,
        'skipped' => 0,
        'not_found' => 0,
        'failed' => []
    ];

    public function __construct(bool $dryRun = false, int $headingRow = 1)
    {
        $this->dryRun = $dryRun;
        $this->headingRow = $headingRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            // Skip empty rows
            if (empty(array_filter($row->toArray(), fn($v) => $v !== null && $v !== ''))) {
                $this->results['skipped']++;
                continue;
            }

            [$ok, $data, $errors] = $this->mapAndValidate($row->toArray(), $i);
            $rowNo = $this->headingRow + 1 + $i;

            if (!$ok) {
                $this->results['failed'][] = [
                    'row' => $rowNo,
                    'errors' => $errors,
                    'data' => $data
                ];
                continue;
            }

            if ($this->dryRun) {
                // Check if customer exists
                $customer = CalonPelanggan::where('reff_id_pelanggan', $data['reff_id_pelanggan'])->first();
                if (!$customer) {
                    $this->results['not_found']++;
                } else {
                    $customer->hasCoordinates() ? $this->results['updated']++ : $this->results['success']++;
                }
                continue;
            }

            $this->buf[] = $data;
            if (count($this->buf) >= $this->chunk) {
                $this->flush();
            }
        }

        $this->flush();
    }

    public function getResults(): array
    {
        return $this->results;
    }

    // ---------------- helpers ----------------

    private function normKey(string $k): string
    {
        $k = trim($k);
        $k = str_replace("\xC2\xA0", ' ', $k); // NBSP → space

        // Remove control characters, special Unicode chars
        $k = preg_replace('/[\x00-\x1F\x7F\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $k);

        // Replace separators with space
        $k = str_replace(['_', '.', '/', '\\'], ' ', $k);

        // Clean up spaces
        $k = preg_replace('/\s+/u', ' ', $k);

        return mb_strtolower($k, 'UTF-8');
    }

    private function mapAndValidate(array $r, int $rowIndex = 0): array
    {
        // Debug logging for first few rows
        if ($rowIndex < 5) {
            Log::info("Coordinate Import Raw Row $rowIndex:", [
                'keys' => array_keys($r),
                'values' => $r
            ]);
        }

        // Normalize keys
        $n = [];
        foreach ($r as $k => $v) {
            $normalizedKey = $this->normKey((string)$k);
            $n[$normalizedKey] = is_string($v) ? trim($v) : $v;
        }

        // Debug normalized data
        if ($rowIndex < 5) {
            Log::info("Coordinate Import Normalized Row $rowIndex:", $n);
        }

        // Map data based on your Excel format: reff_id, lat, lng
        $reffId = $this->toString($n['reff_id'] ?? $n['reff id'] ?? $n['id reff'] ?? null);
        $lat = $this->parseCoordinate($n['lat'] ?? $n['latitude'] ?? null);
        $lng = $this->parseCoordinate($n['lng'] ?? $n['longitude'] ?? null);

        $data = [
            'reff_id_pelanggan' => $reffId,
            'latitude' => $lat,
            'longitude' => $lng,
        ];

        // Validation
        $v = Validator::make($data, [
            'reff_id_pelanggan' => ['required', 'string', 'max:50'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return [$v->passes(), $data, $v->errors()->all()];
    }

    private function flush(): void
    {
        if (!$this->buf) return;

        $reffIds = array_column($this->buf, 'reff_id_pelanggan');

        // Get existing customers
        $customers = CalonPelanggan::whereIn('reff_id_pelanggan', $reffIds)
            ->get()
            ->keyBy('reff_id_pelanggan');

        foreach ($this->buf as $coordData) {
            $reffId = $coordData['reff_id_pelanggan'];
            $customer = $customers->get($reffId);

            if (!$customer) {
                $this->results['not_found']++;
                Log::warning("Customer not found for coordinate update", ['reff_id' => $reffId]);
                continue;
            }

            // Check if customer already has coordinates
            $hadCoordinates = $customer->hasCoordinates();

            // Update coordinates using the model method
            $customer->setCoordinates(
                $coordData['latitude'],
                $coordData['longitude'],
                'excel_import'
            );

            $hadCoordinates ? $this->results['updated']++ : $this->results['success']++;
        }

        $this->buf = [];
    }

    private function toString($v): ?string
    {
        if ($v === null) return null;
        if (is_numeric($v)) {
            return rtrim(rtrim(number_format((float)$v, 10, '.', ''), '0'), '.');
        }
        return trim((string)$v);
    }

    private function parseCoordinate($v): ?float
    {
        if ($v === null || $v === '') return null;

        // Handle comma as decimal separator (Indonesian format)
        if (is_string($v)) {
            $v = str_replace(',', '.', $v);
        }

        // Convert to float
        $parsed = (float)$v;

        // Return null if the coordinate is 0 (likely missing data)
        return $parsed === 0.0 ? null : $parsed;
    }
}