<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PilotComparison;
use App\Models\Pilot;
use App\Models\CalonPelanggan;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class PilotComparisonController extends Controller
{
    /**
     * Display index page with list of pilot upload batches
     */
    public function index(Request $request)
    {
        $query = Pilot::query()
            ->select('batch_id', 'uploaded_by', 'created_at')
            ->selectRaw('COUNT(*) as total_records')
            ->with('uploader')
            ->groupBy('batch_id', 'uploaded_by', 'created_at')
            ->orderBy('created_at', 'desc');

        $batches = $query->paginate(10);

        return view('pilot-comparison.index', compact('batches'));
    }

    /**
     * Display upload form
     */
    public function create()
    {
        return view('pilot-comparison.create');
    }

    /**
     * Import from Google Sheets URL
     */
    public function importFromGoogleSheets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_sheets_url' => 'required|url',
            'sheet_name' => 'nullable|string',
            'sheet_gid' => 'nullable|string',
            'skip_rows' => 'nullable|string',
            'skip_columns' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Increase execution time for large files
            set_time_limit(300); // 5 minutes

            $url = $request->input('google_sheets_url');
            $sheetName = $request->input('sheet_name');
            $sheetGid = $request->input('sheet_gid');
            $skipRowsInput = $request->input('skip_rows', '3'); // Default skip 3 rows (row 4 is header)
            $skipColumnsInput = $request->input('skip_columns', 'A,B'); // Default skip columns A and B
            $debugMode = $request->input('debug_mode', false);

            // Parse skip rows range (e.g., "1-3,5,7-10")
            $skipRows = $this->parseSkipRowsRange($skipRowsInput);

            // Parse skip columns (e.g., "A,B" or "A-C,E,G-J")
            $skipColumns = $this->parseSkipColumnsRange($skipColumnsInput);

            // Debugging info
            $debugInfo = [
                'url_input' => $url,
                'sheet_name' => $sheetName,
                'sheet_gid' => $sheetGid,
                'skip_rows_input' => $skipRowsInput,
                'skip_rows_parsed' => $skipRows,
                'skip_columns_input' => $skipColumnsInput,
                'skip_columns_parsed' => $skipColumns,
            ];

            // Extract spreadsheet ID from URL
            $spreadsheetId = $this->extractSpreadsheetId($url);
            $debugInfo['spreadsheet_id'] = $spreadsheetId;

            if (!$spreadsheetId) {
                if ($debugMode) {
                    return $this->showDebugInfo($debugInfo, 'Spreadsheet ID tidak ditemukan di URL', null);
                }
                return redirect()->back()
                    ->with('error', 'URL Google Sheets tidak valid. Pastikan URL dalam format yang benar.')
                    ->withInput();
            }

            // Build export URL for Excel format (.xlsx)
            $exportUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=xlsx";

            // Add sheet selector (gid has priority)
            if (!empty($sheetGid)) {
                $exportUrl .= "&gid={$sheetGid}";
            }

            $debugInfo['export_url'] = $exportUrl;

            // Download Excel file
            $excelData = $this->downloadGoogleSheetsExcel($exportUrl);
            $debugInfo['file_size'] = strlen($excelData);

            // Save to temporary file for PhpSpreadsheet
            $tempFile = tempnam(sys_get_temp_dir(), 'pilot_') . '.xlsx';
            file_put_contents($tempFile, $excelData);
            $debugInfo['temp_file'] = $tempFile;

            // Parse Excel data with skip rows and columns
            $parseResult = $this->parseExcelWithSkip($tempFile, $sheetName, $skipRows, $skipColumns);
            $pilotData = $parseResult['data'];
            $debugInfo['parsed_records'] = count($pilotData);
            $debugInfo['sheet_used'] = $parseResult['sheet_name'];
            $debugInfo['total_rows'] = $parseResult['total_rows'];
            $debugInfo['data_start_row'] = $parseResult['data_start_row'] ?? null;
            $debugInfo['skip_columns'] = $skipColumns;
            $debugInfo['preview_rows'] = $parseResult['preview_rows'] ?? [];
            $debugInfo['filtered_preview'] = $parseResult['filtered_preview'] ?? [];
            $debugInfo['sample_columns_count'] = $parseResult['sample_columns_count'] ?? 0;
            $debugInfo['sample_first_row'] = $parseResult['sample_first_row'] ?? [];
            $debugInfo['columns_read'] = $parseResult['columns_read'] ?? [];
            $debugInfo['highest_column'] = $parseResult['highest_column'] ?? 'N/A';

            // Delete temporary file
            @unlink($tempFile);

            if (empty($pilotData)) {
                if ($debugMode) {
                    return $this->showDebugInfo($debugInfo, 'Tidak ada data yang berhasil di-parse', null);
                }

                // Store debug info in session for debugging
                session(['pilot_debug' => [
                    'info' => $debugInfo,
                ]]);

                return redirect()->back()
                    ->with('error', 'Tidak ada data yang ditemukan di Google Sheets. Periksa URL dan pengaturan skip rows.')
                    ->with('show_debug', true)
                    ->withInput();
            }

            // If debug mode, show debug info even if there's data
            if ($debugMode) {
                return $this->showDebugInfo($debugInfo, null, 'Data berhasil di-parse! Total: ' . count($pilotData) . ' records');
            }

            // Save data directly to database
            // Validate date parsing before saving
            $dateIssues = $this->validateDateParsing($pilotData);

            DB::beginTransaction();

            try {
                $batchId = Str::uuid()->toString();
                $userId = auth()->id();

                // Save all PILOT data to database
                $savedCount = 0;
                foreach ($pilotData as $pilot) {
                    // Remove temporary debug fields before saving
                    unset($pilot['_original_tanggal_sk']);
                    unset($pilot['_original_tanggal_sr']);
                    unset($pilot['_original_tanggal_gas_in']);

                    $pilot['batch_id'] = $batchId;
                    $pilot['uploaded_by'] = $userId;
                    Pilot::create($pilot);
                    $savedCount++;
                }

                DB::commit();

                // Prepare success message with warnings if any
                $successMessage = 'Data PILOT berhasil diimport dan disimpan! Total: ' . $savedCount . ' records';

                if (count($dateIssues) > 0) {
                    $successMessage .= '<br><br><strong>Peringatan:</strong> ' . count($dateIssues) . ' tanggal gagal di-parse dan disimpan sebagai NULL:';
                    $successMessage .= '<ul>';
                    foreach (array_slice($dateIssues, 0, 10) as $issue) {
                        $successMessage .= '<li>Row ' . $issue['row'] . ' - ' . $issue['nama'] . ' (' . $issue['id_reff'] . '): ' . $issue['field'] . ' = "' . $issue['original_value'] . '"</li>';
                    }
                    if (count($dateIssues) > 10) {
                        $successMessage .= '<li>... dan ' . (count($dateIssues) - 10) . ' lainnya</li>';
                    }
                    $successMessage .= '</ul>';
                }

                // Redirect to show the saved batch
                return redirect()
                    ->route('pilot-comparison.show', $batchId)
                    ->with('success', $successMessage);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // Re-throw to be caught by outer catch
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengakses Google Sheets: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display debug info from session
     */
    public function debugView()
    {
        $debugData = session('pilot_debug');

        if (!$debugData) {
            return redirect()->route('pilot-comparison.create')
                ->with('error', 'Tidak ada data debug. Silakan coba import lagi dengan Debug Mode.');
        }

        return view('pilot-comparison.debug', [
            'debugInfo' => $debugData['info'] ?? [],
            'errorMessage' => 'Import failed - No data parsed',
            'csvData' => $debugData['csv_data'] ?? null,
        ]);
    }

    /**
     * Show debug information for troubleshooting
     */
    private function showDebugInfo(array $debugInfo, ?string $errorMessage = null, ?string $successMessage = null): \Illuminate\View\View
    {
        return view('pilot-comparison.debug', [
            'debugInfo' => $debugInfo,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
        ]);
    }

    /**
     * Extract spreadsheet ID from Google Sheets URL
     */
    private function extractSpreadsheetId(string $url): ?string
    {
        // Pattern: /d/{spreadsheetId}/
        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Download CSV data from Google Sheets export URL
     */
    private function downloadGoogleSheetsCsv(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Failed to download Google Sheets: " . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to access Google Sheets. HTTP Code: " . $httpCode . ". Pastikan sheet dapat diakses publik atau dengan link.");
        }

        return $response;
    }

    /**
     * Download Google Sheets as Excel (.xlsx)
     */
    private function downloadGoogleSheetsExcel(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Failed to download Google Sheets: " . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to access Google Sheets. HTTP Code: " . $httpCode . ". Pastikan sheet dapat diakses publik atau dengan link.");
        }

        return $response;
    }

    /**
     * Parse skip rows range string (e.g., "1-3,5,7-10" or "3")
     * Returns array of row numbers to skip
     */
    private function parseSkipRowsRange(string $input): array
    {
        $input = trim($input);

        // If empty or "0", return empty array
        if (empty($input) || $input === '0') {
            return [];
        }

        // If it's just a number (backward compatibility)
        if (is_numeric($input)) {
            return range(1, (int)$input);
        }

        $skipRows = [];
        $parts = explode(',', $input);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, '-') !== false) {
                // Range: "1-5" or "7-10"
                $range = explode('-', $part);
                if (count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
                    $start = (int)$range[0];
                    $end = (int)$range[1];
                    for ($i = $start; $i <= $end; $i++) {
                        $skipRows[] = $i;
                    }
                }
            } else {
                // Single number: "3" or "5"
                if (!empty($part) && is_numeric($part)) {
                    $skipRows[] = (int)$part;
                }
            }
        }

        return array_unique($skipRows);
    }

    /**
     * Parse skip columns range string (e.g., "A,B" or "A-C,E,G-J")
     * Returns array of column letters to skip
     */
    private function parseSkipColumnsRange(string $input): array
    {
        $input = trim(strtoupper($input)); // Convert to uppercase

        // If empty, return empty array
        if (empty($input)) {
            return [];
        }

        $skipColumns = [];
        $parts = explode(',', $input);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, '-') !== false) {
                // Range: "A-C" or "G-J"
                $range = explode('-', $part);
                if (count($range) === 2) {
                    $start = trim($range[0]);
                    $end = trim($range[1]);

                    // Convert letters to column indices
                    $startIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($start);
                    $endIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($end);

                    // Add all columns in range
                    for ($i = $startIndex; $i <= $endIndex; $i++) {
                        $skipColumns[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    }
                }
            } else {
                // Single column: "A" or "B"
                if (!empty($part) && preg_match('/^[A-Z]+$/', $part)) {
                    $skipColumns[] = $part;
                }
            }
        }

        return array_unique($skipColumns);
    }

    /**
     * Parse Excel file with skip rows and columns using PhpSpreadsheet
     */
    private function parseExcelWithSkip(string $filePath, ?string $sheetName = null, $skipRows = [], $skipColumns = []): array
    {
        $spreadsheet = IOFactory::load($filePath);

        // Select sheet by name or use active sheet
        if (!empty($sheetName)) {
            try {
                $sheet = $spreadsheet->getSheetByName($sheetName);
            } catch (\Exception $e) {
                // If sheet name not found, use first sheet
                $sheet = $spreadsheet->getSheet(0);
            }
        } else {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $sheetNameUsed = $sheet->getTitle();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Convert skipRows to array if it's an integer (backward compatibility)
        if (!is_array($skipRows)) {
            $skipRowsArray = $skipRows > 0 ? range(1, $skipRows) : [];
        } else {
            $skipRowsArray = $skipRows;
        }

        // Convert skipColumns to array if it's an integer (backward compatibility)
        if (!is_array($skipColumns)) {
            // Old format: number of columns to skip from left (e.g., 2 = skip A,B)
            $skipColumnsArray = [];
            for ($i = 1; $i <= $skipColumns; $i++) {
                $skipColumnsArray[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            }
        } else {
            $skipColumnsArray = $skipColumns;
        }

        // Preview first 15 rows for debugging
        $previewRows = [];
        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $rowData = [];
            $columnIndex = 'A';
            while ($columnIndex <= $highestColumn) {
                $cell = $sheet->getCell($columnIndex . $row);

                // Get value to preserve original format and avoid scientific notation
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    $cellValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
                } else {
                    // Check if cell has hyperlink - if yes, use the hyperlink URL instead of cell value
                    $hyperlink = $cell->getHyperlink();
                    if ($hyperlink && $hyperlink->getUrl()) {
                        $cellValue = $hyperlink->getUrl();
                    } else {
                        $dataType = $cell->getDataType();

                        if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
                            $cellValue = $cell->getCalculatedValue();
                        } elseif ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                            $rawValue = $cell->getValue();

                            // Check for custom number format that preserves leading zeros
                            $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

                            // If format has leading zeros (e.g., "0000", "00", etc) or is text-like
                            if (preg_match('/^0+$/', $formatCode) || preg_match('/^0+[^.]/', $formatCode) || $formatCode === '@') {
                                // Use formatted value to preserve leading zeros
                                $formattedValue = $cell->getFormattedValue();
                                // Remove any scientific notation from formatted value
                                if (strpos($formattedValue, 'E') !== false || strpos($formattedValue, 'e') !== false) {
                                    $cellValue = sprintf('%.0f', $rawValue);
                                } else {
                                    $cellValue = $formattedValue;
                                }
                            } else {
                                // No special formatting, convert number without scientific notation
                                if (is_numeric($rawValue) && floor($rawValue) == $rawValue) {
                                    $cellValue = sprintf('%.0f', $rawValue);
                                } else {
                                    $cellValue = rtrim(rtrim(sprintf('%.10f', $rawValue), '0'), '.');
                                }
                            }
                        } else {
                            $cellValue = $cell->getCalculatedValue();
                        }
                    }
                }

                // Convert to string safely
                if (is_array($cellValue) || is_object($cellValue)) {
                    $cellValue = json_encode($cellValue);
                } elseif ($cellValue === null) {
                    $cellValue = '';
                } else {
                    $cellValue = trim((string)$cellValue);
                }

                $rowData[] = $cellValue;
                $columnIndex++;
            }
            $previewRows[] = $rowData;
        }

        // Read data rows - ALL non-skipped rows are DATA (no header row!)
        // Map columns by POSITION (after skipping columns in skipColumnsArray)
        $data = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            // Skip this row if it's in the skip array
            if (in_array($row, $skipRowsArray)) {
                continue;
            }

            // Read all columns, building array by position (skip columns in skipColumnsArray)
            $columns = [];
            $hasData = false;

            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($colIndex = 1; $colIndex <= $maxColIndex; $colIndex++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

                // Skip this column if it's in the skip array
                if (!in_array($columnLetter, $skipColumnsArray)) {
                    $cell = $sheet->getCell($columnLetter . $row);

                    // Handle date values first (before getting value)
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                        $cellValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
                    } else {
                        // Check if cell has hyperlink - if yes, use the hyperlink URL instead of cell value
                        $hyperlink = $cell->getHyperlink();
                        if ($hyperlink && $hyperlink->getUrl()) {
                            $cellValue = $hyperlink->getUrl();
                        } else {
                            $dataType = $cell->getDataType();

                            // For string cells (including numbers stored as text), use calculated value
                            if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
                                $cellValue = $cell->getCalculatedValue();
                            }
                            // For numeric cells, get the raw value and format with full precision
                            elseif ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                                $rawValue = $cell->getValue();

                                // Check for custom number format that preserves leading zeros
                                $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

                                // If format has leading zeros (e.g., "0000", "00", etc) or is text-like
                                if (preg_match('/^0+$/', $formatCode) || preg_match('/^0+[^.]/', $formatCode) || $formatCode === '@') {
                                    // Use formatted value to preserve leading zeros
                                    $formattedValue = $cell->getFormattedValue();
                                    // Remove any scientific notation from formatted value
                                    if (strpos($formattedValue, 'E') !== false || strpos($formattedValue, 'e') !== false) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = $formattedValue;
                                    }
                                } else {
                                    // No special formatting, convert number without scientific notation
                                    if (is_numeric($rawValue) && floor($rawValue) == $rawValue) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = rtrim(rtrim(sprintf('%.10f', $rawValue), '0'), '.');
                                    }
                                }
                            }
                            // For other types, use calculated value
                            else {
                                $cellValue = $cell->getCalculatedValue();
                            }
                        }
                    }

                    // Convert to string safely
                    if (is_array($cellValue) || is_object($cellValue)) {
                        $cellValue = '';
                    } else {
                        $cellValue = $cellValue !== null ? trim((string)$cellValue) : '';
                    }

                    $columns[] = $cellValue;

                    if (!empty($cellValue)) {
                        $hasData = true;
                    }
                }
            }

            // Skip empty rows
            if (!$hasData) {
                continue;
            }

            // Map by column position (index) to database fields
            // After skipping columns, index 0 = first non-skipped column
            // Example: Skip A,B -> index 0 = Column C
            $processedRecord = [
                // Basic Info (indices 0-9)
                'nama' => $columns[0] ?? null,
                'nomor_kartu_identitas' => $columns[1] ?? null,
                'nomor_ponsel' => $columns[2] ?? null,
                'alamat' => $columns[3] ?? null,
                'rt' => $columns[4] ?? null,
                'rw' => $columns[5] ?? null,
                'id_kota_kab' => $columns[6] ?? null,
                'id_kecamatan' => $columns[7] ?? null,
                'id_kelurahan' => $columns[8] ?? null,
                'padukuhan' => $columns[9] ?? null,

                // ID & Status (indices 10-11)
                'id_reff' => $this->cleanReffId($columns[10] ?? ''),
                'penetrasi_pengembangan' => $columns[11] ?? null,

                // Tanggal Pemasangan (indices 12-14)
                'tanggal_terpasang_sk' => $this->parseDate($columns[12] ?? null),
                'tanggal_terpasang_sr' => $this->parseDate($columns[13] ?? null),
                'tanggal_terpasang_gas_in' => $this->parseDate($columns[14] ?? null),

                // Store original date strings for debugging
                '_original_tanggal_sk' => $columns[12] ?? null,
                '_original_tanggal_sr' => $columns[13] ?? null,
                '_original_tanggal_gas_in' => $columns[14] ?? null,

                // Keterangan & Status (indices 15-18)
                'keterangan' => $columns[15] ?? null,
                'batal' => $columns[16] ?? null,
                'keterangan_batal' => $columns[17] ?? null,
                'anomali' => $columns[18] ?? null,

                // Material SK (9 items, indices 19-27)
                'mat_sk_elbow_3_4_to_1_2' => $this->parseInteger($columns[19] ?? null),
                'mat_sk_double_nipple_1_2' => $this->parseInteger($columns[20] ?? null),
                'mat_sk_pipa_galvanize_1_2' => $this->parseInteger($columns[21] ?? null),
                'mat_sk_elbow_1_2' => $this->parseInteger($columns[22] ?? null),
                'mat_sk_ball_valve_1_2' => $this->parseInteger($columns[23] ?? null),
                'mat_sk_nipple_slang_1_2' => $this->parseInteger($columns[24] ?? null),
                'mat_sk_klem_pipa_1_2' => $this->parseInteger($columns[25] ?? null),
                'mat_sk_sockdraft_galvanis_1_2' => $this->parseInteger($columns[26] ?? null),
                'mat_sk_sealtape' => $this->parseInteger($columns[27] ?? null),

                // Material SR (15 items, indices 28-42)
                'mat_sr_ts_63x20mm' => $this->parseInteger($columns[28] ?? null),
                'mat_sr_coupler_20mm' => $this->parseInteger($columns[29] ?? null),
                'mat_sr_pipa_pe_20mm' => $this->parseInteger($columns[30] ?? null),
                'mat_sr_elbow_pe_20mm' => $this->parseInteger($columns[31] ?? null),
                'mat_sr_female_tf_pe_20mm' => $this->parseInteger($columns[32] ?? null),
                'mat_sr_pipa_galvanize_3_4' => $this->parseInteger($columns[33] ?? null),
                'mat_sr_klem_pipa_3_4' => $this->parseInteger($columns[34] ?? null),
                'mat_sr_ball_valves_3_4' => $this->parseInteger($columns[35] ?? null),
                'mat_sr_long_elbow_90_3_4' => $this->parseInteger($columns[36] ?? null),
                'mat_sr_double_nipple_3_4' => $this->parseInteger($columns[37] ?? null),
                'mat_sr_regulator' => $this->parseInteger($columns[38] ?? null),
                'mat_sr_meter_gas_rumah_tangga' => $this->parseInteger($columns[39] ?? null),
                'mat_sr_cassing_1' => $this->parseInteger($columns[40] ?? null),
                'mat_sr_coupling_mgrt' => $this->parseInteger($columns[41] ?? null),
                'mat_sr_sealtape' => $this->parseInteger($columns[42] ?? null),

                // Evidence SK (5 photos, indices 43-47)
                'ev_sk_foto_berita_acara_pemasangan' => $columns[43] ?? null,
                'ev_sk_foto_pneumatik_start' => $columns[44] ?? null,
                'ev_sk_foto_pneumatik_finish' => $columns[45] ?? null,
                'ev_sk_foto_valve_sk' => $columns[46] ?? null,
                'ev_sk_foto_isometrik_sk' => $columns[47] ?? null,

                // Evidence SR (6 photos, indices 48-53)
                'ev_sr_foto_pneumatik_start' => $columns[48] ?? null,
                'ev_sr_foto_pneumatik_finish' => $columns[49] ?? null,
                'ev_sr_foto_jenis_tapping' => $columns[50] ?? null,
                'ev_sr_foto_kedalaman' => $columns[51] ?? null,
                'ev_sr_foto_cassing' => $columns[52] ?? null,
                'ev_sr_foto_isometrik_sr' => $columns[53] ?? null,

                // Evidence MGRT (3 items, indices 54-56)
                'ev_mgrt_foto_meter_gas_rumah_tangga' => $columns[54] ?? null,
                'ev_mgrt_foto_pondasi_mgrt' => $columns[55] ?? null,
                'ev_mgrt_nomor_seri_mgrt' => $columns[56] ?? null,

                // Evidence Gas In (7 items, indices 57-63)
                'ev_gasin_berita_acara_gas_in' => $columns[57] ?? null,
                'ev_gasin_rangkaian_meter_gas_pondasi' => $columns[58] ?? null,
                'ev_gasin_foto_bubble_test' => $columns[59] ?? null,
                'ev_gasin_foto_mgrt' => $columns[60] ?? null,
                'ev_gasin_foto_kompor_menyala_pelanggan' => $columns[61] ?? null,
                'ev_gasin_foto_stiker_sosialisasi' => $columns[62] ?? null,
                'ev_gasin_nomor_seri_mgrt' => $columns[63] ?? null,

                // Review CGP (3 items, indices 64-66)
                'review_cgp_sk' => $columns[64] ?? null,
                'review_cgp_sr' => $columns[65] ?? null,
                'review_cgp_gas_in' => $columns[66] ?? null,

                // Dokumen (4 items, indices 67-70)
                'ba_gas_in' => $columns[67] ?? null,
                'asbuilt_sk' => $columns[68] ?? null,
                'asbuilt_sr' => $columns[69] ?? null,
                'comment_cgp' => $columns[70] ?? null,
            ];

            // Add to data array
            $data[] = $processedRecord;
        }

        // Calculate first data row for debugging info
        $firstDataRow = 1;
        foreach (range(1, $highestRow) as $row) {
            if (!in_array($row, $skipRowsArray)) {
                $firstDataRow = $row;
                break;
            }
        }

        // Get sample first row data for debugging
        $sampleFirstRow = [];
        $sampleColumnsCount = 0;
        if (!empty($data)) {
            // Get first data row columns array before mapping
            $row = $firstDataRow;
            while (in_array($row, $skipRowsArray) && $row <= $highestRow) {
                $row++;
            }

            $columns = [];
            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            for ($colIndex = 1; $colIndex <= $maxColIndex; $colIndex++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                if (!in_array($columnLetter, $skipColumnsArray)) {
                    $cell = $sheet->getCell($columnLetter . $row);

                    // Get value to preserve original format and avoid scientific notation
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                        $cellValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
                    } else {
                        // Check if cell has hyperlink - if yes, use the hyperlink URL instead of cell value
                        $hyperlink = $cell->getHyperlink();
                        if ($hyperlink && $hyperlink->getUrl()) {
                            $cellValue = $hyperlink->getUrl();
                        } else {
                            $dataType = $cell->getDataType();

                            if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
                                $cellValue = $cell->getCalculatedValue();
                            } elseif ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                                $rawValue = $cell->getValue();

                                // Check for custom number format that preserves leading zeros
                                $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

                                // If format has leading zeros (e.g., "0000", "00", etc) or is text-like
                                if (preg_match('/^0+$/', $formatCode) || preg_match('/^0+[^.]/', $formatCode) || $formatCode === '@') {
                                    // Use formatted value to preserve leading zeros
                                    $formattedValue = $cell->getFormattedValue();
                                    // Remove any scientific notation from formatted value
                                    if (strpos($formattedValue, 'E') !== false || strpos($formattedValue, 'e') !== false) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = $formattedValue;
                                    }
                                } else {
                                    // No special formatting, convert number without scientific notation
                                    if (is_numeric($rawValue) && floor($rawValue) == $rawValue) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = rtrim(rtrim(sprintf('%.10f', $rawValue), '0'), '.');
                                    }
                                }
                            } else {
                                $cellValue = $cell->getCalculatedValue();
                            }
                        }
                    }

                    $cellValue = $cellValue !== null ? trim((string)$cellValue) : '';
                    $columns[] = $cellValue;
                }
            }
            $sampleColumnsCount = count($columns);
            $sampleFirstRow = array_slice($columns, 0, 15); // First 15 columns

            // Debug: Show which columns are being read
            $columnsRead = [];
            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            for ($i = 1; $i <= $maxColIndex; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                if (!in_array($colLetter, $skipColumnsArray)) {
                    $columnsRead[] = $colLetter;
                }
            }
        }

        // Create filtered preview (after skip rows and columns applied)
        $filteredPreview = [];
        $previewCount = 0;
        for ($row = 1; $row <= $highestRow && $previewCount < 10; $row++) {
            // Skip rows in skipRowsArray
            if (in_array($row, $skipRowsArray)) {
                continue;
            }

            $rowData = ['_row_number' => $row];
            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            for ($colIndex = 1; $colIndex <= $maxColIndex; $colIndex++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                if (!in_array($columnLetter, $skipColumnsArray)) {
                    $cell = $sheet->getCell($columnLetter . $row);

                    // Get value to preserve original format and avoid scientific notation
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                        $cellValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cell->getValue())->format('Y-m-d');
                    } else {
                        // Check if cell has hyperlink - if yes, use the hyperlink URL instead of cell value
                        $hyperlink = $cell->getHyperlink();
                        if ($hyperlink && $hyperlink->getUrl()) {
                            $cellValue = $hyperlink->getUrl();
                        } else {
                            $dataType = $cell->getDataType();

                            if ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
                                $cellValue = $cell->getCalculatedValue();
                            } elseif ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                                $rawValue = $cell->getValue();

                                // Check for custom number format that preserves leading zeros
                                $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

                                // If format has leading zeros (e.g., "0000", "00", etc) or is text-like
                                if (preg_match('/^0+$/', $formatCode) || preg_match('/^0+[^.]/', $formatCode) || $formatCode === '@') {
                                    // Use formatted value to preserve leading zeros
                                    $formattedValue = $cell->getFormattedValue();
                                    // Remove any scientific notation from formatted value
                                    if (strpos($formattedValue, 'E') !== false || strpos($formattedValue, 'e') !== false) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = $formattedValue;
                                    }
                                } else {
                                    // No special formatting, convert number without scientific notation
                                    if (is_numeric($rawValue) && floor($rawValue) == $rawValue) {
                                        $cellValue = sprintf('%.0f', $rawValue);
                                    } else {
                                        $cellValue = rtrim(rtrim(sprintf('%.10f', $rawValue), '0'), '.');
                                    }
                                }
                            } else {
                                $cellValue = $cell->getCalculatedValue();
                            }
                        }
                    }

                    $cellValue = $cellValue !== null ? trim((string)$cellValue) : '';
                    $rowData[] = $cellValue;
                }
            }
            $filteredPreview[] = $rowData;
            $previewCount++;
        }

        return [
            'data' => $data,
            'sheet_name' => $sheetNameUsed,
            'total_rows' => $highestRow,
            'data_start_row' => $firstDataRow,
            'preview_rows' => $previewRows ?? [], // Original preview
            'filtered_preview' => $filteredPreview, // NEW: Filtered preview
            'sample_columns_count' => $sampleColumnsCount,
            'sample_first_row' => $sampleFirstRow,
            'columns_read' => $columnsRead ?? [],
            'highest_column' => $highestColumn,
        ];
    }

    /**
     * Parse CSV data with skip rows and return additional info
     */
    private function parseCsvDataWithSkipAndInfo(string $csvData, int $skipRows = 0): array
    {
        $result = $this->parseCsvDataWithSkipInternal($csvData, $skipRows);
        return [
            'data' => $result['data'],
            'auto_skip_count' => $result['auto_skip_count'],
            'total_skip_rows' => $result['total_skip_rows'],
            'header_line' => $result['header_line'],
        ];
    }

    /**
     * Parse CSV data with skip rows
     */
    private function parseCsvDataWithSkip(string $csvData, int $skipRows = 0): array
    {
        $result = $this->parseCsvDataWithSkipInternal($csvData, $skipRows);
        return $result['data'];
    }

    /**
     * Internal method to parse CSV data with skip rows
     */
    private function parseCsvDataWithSkipInternal(string $csvData, int $skipRows = 0): array
    {
        $lines = explode("\n", $csvData);
        $data = [];

        // First, skip empty rows at the beginning (rows with only commas)
        $autoSkipCount = 0;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Check if line is empty or only contains commas and whitespace
            if (empty($trimmed) || preg_match('/^[,\s]*$/', $trimmed)) {
                $autoSkipCount++;
            } else {
                // Found first non-empty row
                break;
            }
        }

        // Skip auto-detected empty rows + user-specified skip rows
        $totalSkipRows = $autoSkipCount + $skipRows;
        $lines = array_slice($lines, $totalSkipRows);

        if (empty($lines)) {
            return [];
        }

        // First line after skip is header
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine);

        // Clean and normalize headers
        $headers = array_map(function($header) {
            // Remove line breaks and extra spaces from header
            $header = preg_replace('/\s+/', ' ', $header);
            return trim(strtolower($header));
        }, $headers);

        // Remove empty headers and reindex
        $cleanHeaders = [];
        foreach ($headers as $index => $header) {
            if (!empty($header)) {
                $cleanHeaders[$index] = $header;
            }
        }

        if (empty($cleanHeaders)) {
            return [];
        }

        // Parse data rows
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Skip lines with only commas
            if (preg_match('/^[,\s]*$/', trim($line))) {
                continue;
            }

            $row = str_getcsv($line);

            // Combine headers with row data using cleanHeaders mapping
            $record = [];
            foreach ($cleanHeaders as $index => $header) {
                $record[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            // Skip if all values are empty
            $hasData = false;
            foreach ($record as $value) {
                if (!empty($value)) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                continue;
            }

            // Process the record - support multiple column name variations
            $processedRecord = [
                'reff_id_pelanggan' => $this->cleanReffId(
                    $record['id reff'] ??
                    $record['reff_id_pelanggan'] ??
                    $record['reff_id'] ??
                    $record['id_pelanggan'] ??
                    $record['id pelanggan pln'] ??
                    $record['no'] ??
                    ''
                ),
                'nama_pelanggan' => $record['nama'] ?? $record['nama_pelanggan'] ?? $record['nama lengkap'] ?? null,
                'alamat' => $record['alamat'] ?? $record['address'] ?? null,
                'tanggal_sk' => $this->parseDate($record['sk'] ?? $record['tanggal_sk'] ?? $record['tgl_sk'] ?? null),
                'tanggal_sr' => $this->parseDate($record['sr'] ?? $record['tanggal_sr'] ?? $record['tgl_sr'] ?? null),
                'tanggal_gas_in' => $this->parseDate($record['gas in'] ?? $record['tanggal_gas_in'] ?? $record['tanggal_gasin'] ?? $record['tgl_gas_in'] ?? null),
                'status_sk' => $record['status_sk'] ?? null,
                'status_sr' => $record['status_sr'] ?? null,
                'status_gas_in' => $record['status_gas_in'] ?? $record['status_gasin'] ?? null,
                'raw_data' => $record,
            ];

            // Only add if has reff_id
            if (!empty($processedRecord['reff_id_pelanggan'])) {
                $data[] = $processedRecord;
            }
        }

        return [
            'data' => $data,
            'auto_skip_count' => $autoSkipCount,
            'total_skip_rows' => $totalSkipRows,
            'header_line' => $headerLine ?? '',
        ];
    }

    /**
     * Preview uploaded PILOT file before comparison
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pilot_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $file = $request->file('pilot_file');

            // Parse Excel/CSV file
            $pilotData = $this->parsePilotFile($file);

            // Store file temporarily for later use
            $tempPath = $file->store('temp_pilot_uploads');

            // Store preview data in session
            session(['pilot_preview' => [
                'data' => $pilotData,
                'temp_path' => $tempPath,
                'original_name' => $file->getClientOriginalName(),
            ]]);

            return redirect()->route('pilot-comparison.preview-view');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membaca file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display preview of PILOT data
     */
    public function previewView()
    {
        $previewData = session('pilot_preview');

        if (!$previewData) {
            return redirect()->route('pilot-comparison.create')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }

        // Read data from temporary file
        if (isset($previewData['temp_file']) && file_exists($previewData['temp_file'])) {
            $pilotData = json_decode(file_get_contents($previewData['temp_file']), true);
        } else {
            // Fallback to old method (data in session)
            $pilotData = $previewData['data'] ?? [];
        }

        $fileName = $previewData['original_name'];

        // Get column names from first record, exclude raw_data
        $columns = !empty($pilotData) ? array_keys($pilotData[0]) : [];
        $columns = array_filter($columns, function($col) {
            return $col !== 'raw_data';
        });

        return view('pilot-comparison.preview', compact('pilotData', 'fileName', 'columns'));
    }

    /**
     * Handle file upload and run comparison
     */
    public function store(Request $request)
    {
        // Check if coming from preview
        if ($request->has('from_preview') && session()->has('pilot_preview')) {
            $previewData = session('pilot_preview');

            // Read data from temporary file
            if (isset($previewData['temp_file']) && file_exists($previewData['temp_file'])) {
                $pilotData = json_decode(file_get_contents($previewData['temp_file']), true);
            } else {
                // Fallback to old method (data in session)
                $pilotData = $previewData['data'] ?? [];
            }

            try {
                // Validate date parsing before saving
                $dateIssues = $this->validateDateParsing($pilotData);

                DB::beginTransaction();

                $batchId = Str::uuid()->toString();
                $userId = auth()->id();

                // Save all PILOT data to database
                $savedCount = 0;
                foreach ($pilotData as $pilot) {
                    // Remove temporary debug fields before saving
                    unset($pilot['_original_tanggal_sk']);
                    unset($pilot['_original_tanggal_sr']);
                    unset($pilot['_original_tanggal_gas_in']);

                    $pilot['batch_id'] = $batchId;
                    $pilot['uploaded_by'] = $userId;
                    \App\Models\Pilot::create($pilot);
                    $savedCount++;
                }

                DB::commit();

                // Delete temporary file if exists
                if (isset($previewData['temp_file']) && file_exists($previewData['temp_file'])) {
                    @unlink($previewData['temp_file']);
                }

                // Clear session
                session()->forget('pilot_preview');

                // Prepare success message with warnings if any
                $successMessage = 'Data PILOT berhasil diupload! Total: ' . $savedCount . ' records';

                if (count($dateIssues) > 0) {
                    $successMessage .= '<br><br><strong>Peringatan:</strong> ' . count($dateIssues) . ' tanggal gagal di-parse dan disimpan sebagai NULL:';
                    $successMessage .= '<ul>';
                    foreach (array_slice($dateIssues, 0, 10) as $issue) {
                        $successMessage .= '<li>Row ' . $issue['row'] . ' - ' . $issue['nama'] . ' (' . $issue['id_reff'] . '): ' . $issue['field'] . ' = "' . $issue['original_value'] . '"</li>';
                    }
                    if (count($dateIssues) > 10) {
                        $successMessage .= '<li>... dan ' . (count($dateIssues) - 10) . ' lainnya</li>';
                    }
                    $successMessage .= '</ul>';
                }

                return redirect()
                    ->route('pilot-comparison.index')
                    ->with('success', $successMessage);

            } catch (\Exception $e) {
                DB::rollBack();

                // Delete temporary file if exists
                if (isset($previewData['temp_file']) && file_exists($previewData['temp_file'])) {
                    @unlink($previewData['temp_file']);
                }

                return redirect()->back()
                    ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        // Normal upload (direct comparison without preview)
        $validator = Validator::make($request->all(), [
            'pilot_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $file = $request->file('pilot_file');
            $batchId = Str::uuid()->toString();

            // Parse Excel/CSV file
            $pilotData = $this->parsePilotFile($file);

            // Run comparison
            $results = $this->compareWithDatabase($pilotData, $batchId);

            DB::commit();

            return redirect()
                ->route('pilot-comparison.show', ['batch' => $batchId])
                ->with('success', 'Data PILOT berhasil diupload dan dibandingkan dengan database!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display comparison results for a specific batch
     */
    public function show(Request $request, string $batch)
    {
        $query = Pilot::query()
            ->where('batch_id', $batch)
            ->with('uploader');

        // Search by id_reff or name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id_reff', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $pilots = $query->paginate(50);

        // Get batch summary
        $summary = Pilot::where('batch_id', $batch)
            ->selectRaw('COUNT(*) as total')
            ->first();

        return view('pilot-comparison.show', compact('pilots', 'batch', 'summary'));
    }

    /**
     * Delete a pilot upload batch
     */
    public function destroy(string $batch)
    {
        try {
            Pilot::where('batch_id', $batch)->delete();

            return redirect()
                ->route('pilot-comparison.index')
                ->with('success', 'Batch PILOT berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Export comparison results to Excel
     */
    public function export(string $batch)
    {
        $comparisons = PilotComparison::where('batch_id', $batch)->get();

        return Excel::download(
            new \App\Exports\PilotComparisonExport($comparisons),
            'pilot-comparison-' . $batch . '.xlsx'
        );
    }

    /**
     * Parse PILOT file (Excel or CSV)
     * Expected columns: reff_id_pelanggan, nama_pelanggan, alamat, tanggal_sk, tanggal_sr, tanggal_gas_in, status_sk, status_sr, status_gas_in
     */
    private function parsePilotFile($file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Assume first row is header
        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        $data = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($headers, $row);
            $data[] = [
                'reff_id_pelanggan' => $this->cleanReffId($record['reff_id_pelanggan'] ?? $record['reff_id'] ?? ''),
                'nama_pelanggan' => $record['nama_pelanggan'] ?? $record['nama'] ?? null,
                'alamat' => $record['alamat'] ?? null,
                'tanggal_sk' => $this->parseDate($record['tanggal_sk'] ?? null),
                'tanggal_sr' => $this->parseDate($record['tanggal_sr'] ?? null),
                'tanggal_gas_in' => $this->parseDate($record['tanggal_gas_in'] ?? $record['tanggal_gasin'] ?? null),
                'status_sk' => $record['status_sk'] ?? null,
                'status_sr' => $record['status_sr'] ?? null,
                'status_gas_in' => $record['status_gas_in'] ?? $record['status_gasin'] ?? null,
                'raw_data' => $record, // Keep original data
            ];
        }

        return $data;
    }

    /**
     * Clean and normalize reff_id_pelanggan
     */
    private function cleanReffId($reffId): string
    {
        // Remove any whitespace
        $reffId = trim($reffId);

        // If it starts with "00" and is 8 digits, remove the "00" prefix
        if (preg_match('/^00(\d{6})$/', $reffId, $matches)) {
            return $matches[1];
        }

        return $reffId;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // If it's an Excel date serial number
            if (is_numeric($dateValue)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                return $date->format('Y-m-d');
            }

            // Normalize the date string
            $dateValue = trim((string)$dateValue);

            // Convert Indonesian month names to English
            $monthMap = [
                // Standard Indonesian months
                'januari' => 'January',
                'februari' => 'February',
                'maret' => 'March',
                'april' => 'April',
                'mei' => 'May',
                'juni' => 'June',
                'juli' => 'July',
                'agustus' => 'August',
                'september' => 'September',
                'oktober' => 'October',
                'november' => 'November',
                'desember' => 'December',

                // Common typos and variations
                'octob' => 'October',
                'nopember' => 'November',
                'des' => 'December',
                'okt' => 'October',
                'nov' => 'November',
                'sept' => 'September',
                'ags' => 'August',
                'peb' => 'February',
                'feb' => 'February',
                'mar' => 'March',
                'apr' => 'April',
                'jun' => 'June',
                'jul' => 'July',
                'aug' => 'August',
                'sep' => 'September',
                'oct' => 'October',
                'dec' => 'December',

                // Abbreviated Indonesian
                'jan' => 'January',
                'agt' => 'August',

                // Common misspellings
                'okteber' => 'October',
                'nopembar' => 'November',
                'desembar' => 'December',
                'agustu' => 'August',
            ];

            // Convert to lowercase for matching
            $dateValueLower = mb_strtolower($dateValue);

            // Replace Indonesian/typo month names with English
            foreach ($monthMap as $indonesian => $english) {
                if (stripos($dateValueLower, $indonesian) !== false) {
                    $dateValue = preg_replace('/\b' . $indonesian . '\b/i', $english, $dateValue);
                    break;
                }
            }

            // Try common date formats
            $formats = [
                'd-m-Y',
                'd/m/Y',
                'Y-m-d',
                'd M Y',
                'd F Y',
                'j F Y',
                'j M Y',
                'd-M-Y',
                'd/M/Y',
                'Y/m/d',
                'd.m.Y',
            ];

            foreach ($formats as $format) {
                $parsedDate = \DateTime::createFromFormat($format, $dateValue);
                if ($parsedDate !== false) {
                    return $parsedDate->format('Y-m-d');
                }
            }

            // Fallback: Try Carbon parse
            $date = Carbon::parse($dateValue);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Log the failed date parsing for debugging
            \Log::warning("Failed to parse date: " . $dateValue . " - Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse integer value from cell
     */
    private function parseInteger($value): ?int
    {
        if (empty($value) || !is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }

    /**
     * Validate and report date parsing issues
     */
    private function validateDateParsing(array $records): array
    {
        $issues = [];
        $dateFields = [
            'tanggal_terpasang_sk' => '_original_tanggal_sk',
            'tanggal_terpasang_sr' => '_original_tanggal_sr',
            'tanggal_terpasang_gas_in' => '_original_tanggal_gas_in',
        ];

        foreach ($records as $index => $record) {
            $rowNumber = $index + 1;
            foreach ($dateFields as $parsedField => $originalField) {
                $originalValue = $record[$originalField] ?? null;
                $parsedValue = $record[$parsedField] ?? null;

                // Check if original has value but parsed is null
                if (!empty($originalValue) && is_null($parsedValue)) {
                    $issues[] = [
                        'row' => $rowNumber,
                        'id_reff' => $record['id_reff'] ?? 'Unknown',
                        'nama' => $record['nama'] ?? 'Unknown',
                        'field' => $parsedField,
                        'original_value' => $originalValue,
                        'error' => 'Failed to parse date',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Compare PILOT data with database records
     */
    private function compareWithDatabase(array $pilotData, string $batchId): array
    {
        $results = [];
        $userId = auth()->id();

        foreach ($pilotData as $pilot) {
            $reffId = $pilot['reff_id_pelanggan'];

            // Find in database
            $pelanggan = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();

            if (!$pelanggan) {
                // Not found in database
                $results[] = PilotComparison::create([
                    'batch_id' => $batchId,
                    'reff_id_pelanggan' => $reffId,
                    'nama_pelanggan' => $pilot['nama_pelanggan'],
                    'alamat' => $pilot['alamat'],
                    'pilot_tanggal_sk' => $pilot['tanggal_sk'],
                    'pilot_tanggal_sr' => $pilot['tanggal_sr'],
                    'pilot_tanggal_gas_in' => $pilot['tanggal_gas_in'],
                    'pilot_status_sk' => $pilot['status_sk'],
                    'pilot_status_sr' => $pilot['status_sr'],
                    'pilot_status_gas_in' => $pilot['status_gas_in'],
                    'pilot_raw_data' => $pilot['raw_data'],
                    'comparison_status' => PilotComparison::STATUS_MISSING_IN_DB,
                    'differences' => ['missing' => 'Data tidak ditemukan di database'],
                    'uploaded_by' => $userId,
                ]);
                continue;
            }

            // Get module data
            $skData = $pelanggan->skData;
            $srData = $pelanggan->srData;
            $gasInData = $pelanggan->gasInData;

            // Extract database dates and status
            $dbDates = [
                'sk' => $skData?->tanggal_instalasi?->format('Y-m-d'),
                'sr' => $srData?->tanggal_pemasangan?->format('Y-m-d'),
                'gas_in' => $gasInData?->tanggal_gas_in?->format('Y-m-d'),
            ];

            $dbStatus = [
                'sk' => $skData?->status,
                'sr' => $srData?->status,
                'gas_in' => $gasInData?->status,
            ];

            // Compare dates and status
            $differences = [];
            $hasDifferences = false;

            // Compare SK
            if ($pilot['tanggal_sk'] && $dbDates['sk'] && $pilot['tanggal_sk'] !== $dbDates['sk']) {
                $differences['tanggal_sk'] = [
                    'pilot' => $pilot['tanggal_sk'],
                    'db' => $dbDates['sk'],
                ];
                $hasDifferences = true;
            }

            // Compare SR
            if ($pilot['tanggal_sr'] && $dbDates['sr'] && $pilot['tanggal_sr'] !== $dbDates['sr']) {
                $differences['tanggal_sr'] = [
                    'pilot' => $pilot['tanggal_sr'],
                    'db' => $dbDates['sr'],
                ];
                $hasDifferences = true;
            }

            // Compare GAS IN
            if ($pilot['tanggal_gas_in'] && $dbDates['gas_in'] && $pilot['tanggal_gas_in'] !== $dbDates['gas_in']) {
                $differences['tanggal_gas_in'] = [
                    'pilot' => $pilot['tanggal_gas_in'],
                    'db' => $dbDates['gas_in'],
                ];
                $hasDifferences = true;
            }

            // Determine comparison status
            $comparisonStatus = $hasDifferences
                ? PilotComparison::STATUS_DATE_MISMATCH
                : PilotComparison::STATUS_MATCH;

            // Create comparison record
            $results[] = PilotComparison::create([
                'batch_id' => $batchId,
                'reff_id_pelanggan' => $reffId,
                'nama_pelanggan' => $pelanggan->nama_pelanggan,
                'alamat' => $pelanggan->alamat,
                'pilot_tanggal_sk' => $pilot['tanggal_sk'],
                'pilot_tanggal_sr' => $pilot['tanggal_sr'],
                'pilot_tanggal_gas_in' => $pilot['tanggal_gas_in'],
                'pilot_status_sk' => $pilot['status_sk'],
                'pilot_status_sr' => $pilot['status_sr'],
                'pilot_status_gas_in' => $pilot['status_gas_in'],
                'pilot_raw_data' => $pilot['raw_data'],
                'db_tanggal_sk' => $dbDates['sk'],
                'db_tanggal_sr' => $dbDates['sr'],
                'db_tanggal_gas_in' => $dbDates['gas_in'],
                'db_status_sk' => $dbStatus['sk'],
                'db_status_sr' => $dbStatus['sr'],
                'db_status_gas_in' => $dbStatus['gas_in'],
                'comparison_status' => $comparisonStatus,
                'differences' => $differences,
                'uploaded_by' => $userId,
            ]);
        }

        return $results;
    }
}
