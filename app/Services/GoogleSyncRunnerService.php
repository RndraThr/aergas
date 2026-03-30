<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GoogleSyncRunnerService
{
    private GoogleSheetService $sheetService;
    private RecapDataService $recapService;

    public function __construct(GoogleSheetService $sheetService, RecapDataService $recapService)
    {
        $this->sheetService = $sheetService;
        $this->recapService = $recapService;
    }

    /**
     * Executes the heavy sync payload process securely.
     * Returns total synced rows.
     *
     * @throws Exception
     */
    public function execute(string $spreadsheetId, string $sheetName, int $startRow = 5): int
    {
        // Disable query log to save memory and packet size
        DB::connection()->disableQueryLog();
        set_time_limit(300);

        // 1. Clear Sheet Data Only (Preserve Headers above, so start clearing from dynamic row)
        $headers = $this->recapService->getHeaders();
        // Plus 1 for the 'No' column we artificially inject
        $maxCol = $this->numberToColumnName(count($headers) + 1);
        $safeSheetName = "'" . str_replace("'", "''", $sheetName) . "'";

        // Clear from Row target to the end
        $clearRange = "{$safeSheetName}!A{$startRow}:{$maxCol}";
        $this->sheetService->clearSheet($spreadsheetId, $clearRange);

        // 2. Process and Write Data in Chunks
        $totalSynced = 0;
        $chunkBatchSize = 100;

        $query = $this->recapService->buildQuery();

        $batchData = [];
        $rowNumber = 1; // Start numbering from 1
        $currentRow = $startRow; // Data naturally starts at specified row

        // Cursor is memory efficient
        foreach ($query->cursor() as $customer) {
            // Map data inside the loop to avoid holding objects
            $rowData = $this->recapService->mapCustomerRow($customer, $currentRow);

            // Add "No" (Number) at the beginning of the row
            array_unshift($rowData, $rowNumber++); // Prepend and increment

            $batchData[] = $rowData;
            $currentRow++;

            // Flush batch
            if (count($batchData) >= $chunkBatchSize) {
                $calcRange = "{$safeSheetName}!A{$startRow}";
                $this->sheetService->appendSheet($spreadsheetId, $calcRange, $batchData);

                $totalSynced += count($batchData);
                $batchData = [];

                unset($customer);
            }
        }

        // Write remaining data
        if (!empty($batchData)) {
            $calcRange = "{$safeSheetName}!A{$startRow}";
            $this->sheetService->appendSheet($spreadsheetId, $calcRange, $batchData);
            $totalSynced += count($batchData);
        }

        return $totalSynced;
    }

    private function numberToColumnName(int $num): string
    {
        $numeric = $num - 1;
        $alphabet = range('A', 'Z');
        if ($numeric < 26) {
            return $alphabet[$numeric];
        } else {
            // Simple support for AA, AB... (up to ZZ is enough for < 700 cols)
            $first = floor($numeric / 26) - 1;
            $second = $numeric % 26;
            return $alphabet[$first] . $alphabet[$second];
        }
    }
}
