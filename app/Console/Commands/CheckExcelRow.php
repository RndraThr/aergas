<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CheckExcelRow extends Command
{
    protected $signature = 'jalur:check-excel-row
                            {excel_file : Path to Excel file}
                            {line_number : Line number to check (can be just code like 030 or full like 63-PRW-LN030)}
                            {tanggal : Date to check (Y-m-d format)}';

    protected $description = 'Check specific row in Excel file';

    public function handle()
    {
        $excelFile = $this->argument('excel_file');
        $searchLineNumber = $this->argument('line_number');
        $searchTanggal = $this->argument('tanggal');

        if (!file_exists($excelFile)) {
            $this->error("Excel file not found: {$excelFile}");
            return 1;
        }

        // Extract just the line code if full format given (63-PRW-LN030 -> 030)
        $searchLineCode = $searchLineNumber;
        if (preg_match('/LN(\d+)/', $searchLineNumber, $matches)) {
            $searchLineCode = $matches[1];
            $this->info("Extracted line code: {$searchLineCode} from {$searchLineNumber}");
        }

        $this->info("Searching for: {$searchLineCode} on {$searchTanggal}");
        $this->newLine();

        try {
            $spreadsheet = IOFactory::load($excelFile);
            $worksheet = $spreadsheet->getActiveSheet();

            $found = false;

            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                $lineNumber = $worksheet->getCell('C' . $row)->getValue();
                $tanggalValue = $worksheet->getCell('D' . $row)->getValue();

                if (is_numeric($tanggalValue)) {
                    $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalValue)->format('Y-m-d');
                } else {
                    $tanggal = $tanggalValue;
                }

                // Normalize line number for comparison (remove leading zeros)
                $normalizedLineNumber = ltrim($lineNumber, '0');
                $normalizedSearchCode = ltrim($searchLineCode, '0');

                if (($lineNumber === $searchLineCode || $normalizedLineNumber === $normalizedSearchCode) && $tanggal === $searchTanggal) {
                    $found = true;
                    $this->info("✅ Found at Excel row: {$row}");
                    $this->line("Line Number: {$lineNumber}");
                    $this->line("Tanggal: {$tanggal}");
                    $this->newLine();

                    // Check all columns for hyperlinks
                    $columns = ['G', 'H', 'J', 'K', 'L', 'M', 'N'];
                    $columnNames = [
                        'G' => 'Lowering (Penggelaran)',
                        'H' => 'Bongkaran',
                        'J' => 'Cassing',
                        'K' => 'Marker Tape',
                        'L' => 'Concrete Slab',
                        'M' => 'MC 0',
                        'N' => 'MC 100',
                    ];

                    $this->info("Hyperlinks in this row:");
                    foreach ($columns as $col) {
                        $cell = $worksheet->getCell($col . $row);
                        $value = $cell->getValue();

                        $this->line("Column {$col} ({$columnNames[$col]}): ");
                        $this->line("  Value: " . ($value ?? 'NULL'));

                        if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                            $url = $cell->getHyperlink()->getUrl();
                            $this->line("  URL: {$url}");

                            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
                                $fileId = $matches[1];
                                $this->line("  File ID: {$fileId}");
                            }
                        } else {
                            $this->line("  No hyperlink");
                        }
                        $this->newLine();
                    }

                    break;
                }
            }

            if (!$found) {
                $this->warn("❌ Not found in Excel file");
                $this->newLine();

                // Show similar records
                $this->info("Showing all records for line number: {$searchLineCode}");
                for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                    $lineNumber = $worksheet->getCell('C' . $row)->getValue();
                    $normalizedLineNumber = ltrim($lineNumber, '0');
                    $normalizedSearchCode = ltrim($searchLineCode, '0');

                    if ($lineNumber === $searchLineCode || $normalizedLineNumber === $normalizedSearchCode) {
                        $tanggalValue = $worksheet->getCell('D' . $row)->getValue();
                        if (is_numeric($tanggalValue)) {
                            $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalValue)->format('Y-m-d');
                        } else {
                            $tanggal = $tanggalValue;
                        }
                        $this->line("Row {$row}: {$lineNumber} - {$tanggal}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("Failed to read Excel: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
