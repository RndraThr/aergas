<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JalurLoweringData;
use App\Models\JalurLineNumber;
use App\Models\PhotoApproval;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class DiagnoseJalurPhotoMapping extends Command
{
    protected $signature = 'jalur:diagnose-photo-mapping
                            {excel_file : Path to Excel file to check}
                            {--line-number= : Check specific line number only}
                            {--fix : Fix the mismatched photos}
                            {--dry-run : Preview changes without applying}';

    protected $description = 'Diagnose photo mapping issues between Excel and database';

    private array $hyperlinks = [];
    private array $mismatches = [];

    public function handle()
    {
        $excelFile = $this->argument('excel_file');
        $specificLineNumber = $this->option('line-number');
        $fix = $this->option('fix');
        $dryRun = $this->option('dry-run');

        if (!file_exists($excelFile)) {
            $this->error("Excel file not found: {$excelFile}");
            return 1;
        }

        $this->info("Diagnosing photo mapping for: {$excelFile}");
        $this->newLine();

        // Step 1: Extract hyperlinks from Excel
        $this->info("Step 1: Extracting hyperlinks from Excel...");
        $this->extractHyperlinks($excelFile);
        $this->info("Found " . count($this->hyperlinks) . " rows with hyperlinks");
        $this->newLine();

        // Step 2: Compare with database
        $this->info("Step 2: Comparing with database records...");
        $this->compareWithDatabase($specificLineNumber);
        $this->newLine();

        // Step 3: Display results
        if (empty($this->mismatches)) {
            $this->info("✅ No mismatches found! All photos are correctly mapped.");
            return 0;
        }

        $this->warn("⚠️  Found " . count($this->mismatches) . " mismatches:");
        $this->newLine();

        $this->displayMismatches();

        // Step 4: Fix if requested
        if ($fix || $dryRun) {
            $this->newLine();
            if ($dryRun) {
                $this->info("🔍 DRY RUN - No changes will be made");
            } else {
                $this->warn("🔧 FIXING mismatched photos...");
            }
            $this->fixMismatches($dryRun);
        } else {
            $this->newLine();
            $this->info("💡 To fix these mismatches, run with --fix option");
            $this->info("   To preview fixes without applying, use --dry-run");
        }

        return 0;
    }

    private function extractHyperlinks(string $filePath): void
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $progressBar = $this->output->createProgressBar($highestRow - 1);
            $progressBar->start();

            // Start from row 2 (skip header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [
                    'row' => $row,
                    'line_number' => null,
                    'tanggal' => null,
                    'hyperlinks' => []
                ];

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell = $worksheet->getCell($cellCoordinate);
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

                    // Get line_number from column C
                    if ($columnLetter === 'C') {
                        $rowData['line_number'] = $cell->getValue();
                    }

                    // Get tanggal from column D
                    if ($columnLetter === 'D') {
                        $value = $cell->getValue();
                        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                            $value = $value->getPlainText();
                        }
                        if (is_numeric($value)) {
                            // Excel date serial number
                            $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                        }
                        $rowData['tanggal'] = $value;
                    }

                    // Check if cell has hyperlink
                    if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                        $url = $cell->getHyperlink()->getUrl();

                        // Extract Google Drive file ID
                        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
                            $fileId = $matches[1];
                            $rowData['hyperlinks'][$columnLetter] = [
                                'url' => $url,
                                'file_id' => $fileId
                            ];
                        }
                    }
                }

                if (!empty($rowData['hyperlinks'])) {
                    $this->hyperlinks[$row] = $rowData;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("Failed to extract hyperlinks: " . $e->getMessage());
            Log::error("Failed to extract hyperlinks", [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function compareWithDatabase(?string $specificLineNumber): void
    {
        $query = JalurLoweringData::with(['lineNumber', 'photoApprovals']);

        if ($specificLineNumber) {
            $query->whereHas('lineNumber', function ($q) use ($specificLineNumber) {
                $q->where('line_number', $specificLineNumber);
            });
        }

        $loweringRecords = $query->get();

        $progressBar = $this->output->createProgressBar($loweringRecords->count());
        $progressBar->start();

        foreach ($loweringRecords as $lowering) {
            $lineNumber = $lowering->lineNumber->line_number;
            $tanggal = $lowering->tanggal_jalur->format('Y-m-d');

            // Find matching Excel row
            $excelMatch = null;
            foreach ($this->hyperlinks as $row => $data) {
                if ($data['line_number'] === $lineNumber && $data['tanggal'] === $tanggal) {
                    $excelMatch = $data;
                    $excelMatch['excel_row'] = $row;
                    break;
                }
            }

            if (!$excelMatch) {
                // Record exists in DB but not in Excel
                continue;
            }

            // Check lowering photo (column G)
            $expectedFileId = $excelMatch['hyperlinks']['G']['file_id'] ?? null;
            $loweringPhoto = $lowering->photoApprovals()
                ->where('photo_field_name', 'foto_evidence_penggelaran_bongkaran')
                ->first();

            if ($expectedFileId && $loweringPhoto) {
                // Extract file ID from photo_url
                $actualFileId = null;
                if ($loweringPhoto->photo_url && preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $loweringPhoto->photo_url, $matches)) {
                    $actualFileId = $matches[1];
                }

                if ($actualFileId !== $expectedFileId) {
                    $this->mismatches[] = [
                        'lowering_id' => $lowering->id,
                        'line_number' => $lineNumber,
                        'tanggal' => $tanggal,
                        'excel_row' => $excelMatch['excel_row'],
                        'field' => 'foto_evidence_penggelaran_bongkaran',
                        'expected_file_id' => $expectedFileId,
                        'expected_url' => $excelMatch['hyperlinks']['G']['url'],
                        'actual_file_id' => $actualFileId,
                        'actual_url' => $loweringPhoto->photo_url,
                        'photo_id' => $loweringPhoto->id,
                    ];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function displayMismatches(): void
    {
        foreach ($this->mismatches as $index => $mismatch) {
            $number = $index + 1;
            $this->warn("#{$number} - {$mismatch['line_number']} ({$mismatch['tanggal']})");
            $this->line("  Excel Row: {$mismatch['excel_row']}");
            $this->line("  Lowering ID: {$mismatch['lowering_id']}");
            $this->line("  Field: {$mismatch['field']}");
            $this->line("  Expected (Excel): {$mismatch['expected_file_id']}");
            $this->line("  Actual (Database): {$mismatch['actual_file_id']}");
            $this->newLine();
        }
    }

    private function fixMismatches(bool $dryRun): void
    {
        $fixed = 0;
        $failed = 0;

        foreach ($this->mismatches as $mismatch) {
            $this->info("Fixing {$mismatch['line_number']} ({$mismatch['tanggal']})...");

            if ($dryRun) {
                $this->line("  Would update photo_url from:");
                $this->line("    {$mismatch['actual_url']}");
                $this->line("  to:");
                $this->line("    {$mismatch['expected_url']}");
                $this->line("  Would set drive_file_id = NULL (to trigger re-copy)");
                $fixed++;
            } else {
                try {
                    // Update photo_approval record
                    $photo = PhotoApproval::find($mismatch['photo_id']);
                    if ($photo) {
                        $photo->photo_url = $mismatch['expected_url'];
                        $photo->drive_file_id = null; // Reset to trigger re-copy
                        $photo->storage_path = null;
                        $photo->save();

                        $this->info("  ✅ Fixed!");
                        $fixed++;
                    } else {
                        $this->error("  ❌ Photo record not found!");
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $this->error("  ❌ Failed: " . $e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Fixed: {$fixed}");
        if ($failed > 0) {
            $this->warn("  Failed: {$failed}");
        }

        if (!$dryRun && $fixed > 0) {
            $this->newLine();
            $this->info("💡 Next steps:");
            $this->info("  1. Run: php artisan jalur:fix-photo-copy");
            $this->info("     This will copy the corrected photos from Google Drive");
        }
    }
}
