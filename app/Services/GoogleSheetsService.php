<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\JalurLoweringData;
use App\Models\JalurJointData;

class GoogleSheetsService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName('Aergas Jalur Integration');
            $credentialsPath = config('services.google_sheets.credentials_path');

            if (!file_exists($credentialsPath)) {
                throw new \Exception("Google Service Account credentials not found at: {$credentialsPath}");
            }

            $this->client->setAuthConfig($credentialsPath);
            $this->client->setScopes([Sheets::SPREADSHEETS]);
            $this->service = new Sheets($this->client);
            Log::info('Google Sheets Service initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Sheets Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Appends row and returns the Row Number (int) on success, or false on failure.
     */
    public function appendRow(string $sheetName, array $values)
    {
        try {
            $range = "{$sheetName}!A:Z";
            $body = new Sheets\ValueRange(['values' => [$values]]);
            $params = ['valueInputOption' => 'USER_ENTERED'];
            $result = $this->service->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);

            // Extract Row Number from updatedRange (e.g., "PE!B150:AF150")
            // Match any column letter start
            $updatedRange = $result->getUpdates()->getUpdatedRange();
            if (preg_match('/!([A-Z]+)(\d+):/', $updatedRange, $m)) {
                Log::info("Row appended to {$sheetName} at row {$m[2]}");
                return (int) $m[2];
            }

            Log::info("Row appended to {$sheetName} (Row number detection failed from {$updatedRange})");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to append row to {$sheetName}: " . $e->getMessage());
            return false;
        }
    }

    public function updateRange(string $range, array $values): bool
    {
        try {
            $body = new Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'USER_ENTERED'];
            $this->service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
            Log::info("Range updated: {$range}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update range {$range}: " . $e->getMessage());
            return false;
        }
    }

    private function getPhotoUrl($model, string $photoFieldName): string
    {
        if (!$model->relationLoaded('photoApprovals')) {
            $model->load('photoApprovals');
        }

        $photo = $model->photoApprovals->firstWhere('photo_field_name', $photoFieldName);

        if ($photo && !empty($photo->drive_link)) {
            return $photo->drive_link;
        }

        // Fallback: Check photo_url if it contains a Drive link (backward compatibility)
        if ($photo && !empty($photo->photo_url) && str_contains($photo->photo_url, 'drive.google.com')) {
            return $photo->photo_url;
        }

        return '';
    }

    public function generateHyperlink(string $url, string $label): string
    {
        if (empty($url))
            return $label;
        $safeUrl = str_replace('"', '%22', $url);
        $safeLabel = str_replace('"', '""', $label);
        // Use semicolon for Indonesian locale support
        return '=HYPERLINK("' . $safeUrl . '"; "' . $safeLabel . '")';
    }

    private function formatIndoDate($date)
    {
        if (!$date)
            return '';
        if (is_string($date))
            $date = Carbon::parse($date);

        $months = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des'
        ];
        return "{$date->day}-{$months[$date->month]}-{$date->year}";
    }

    public function formatLoweringRow($lowering): array
    {
        $tanggal = $this->formatIndoDate($lowering->tanggal_jalur);

        $clusterName = '';
        $clusterCode = '';
        if ($lowering->lineNumber && $lowering->lineNumber->cluster) {
            $clusterName = $lowering->lineNumber->cluster->nama_cluster;
            $clusterCode = $lowering->lineNumber->cluster->code_cluster;
        }

        $testPackage = "TP-" . $clusterCode;

        $mainPhotoUrl = $this->getPhotoUrl($lowering, 'foto_evidence_penggelaran_bongkaran');
        $cassingPhotoUrl = $this->getPhotoUrl($lowering, 'foto_evidence_cassing');
        $markerTapePhotoUrl = $this->getPhotoUrl($lowering, 'foto_evidence_marker_tape');
        $concreteSlabPhotoUrl = $this->getPhotoUrl($lowering, 'foto_evidence_concrete_slab');
        $landasanPhotoUrl = $this->getPhotoUrl($lowering, 'foto_evidence_landasan');

        $valLink = function ($val, $url) {
            if (empty($url))
                return $val;
            return $this->generateHyperlink($url, (string) $val);
        };

        $penggelaranVal = $valLink($lowering->penggelaran ?? '', $mainPhotoUrl);
        $bongkaranVal = $valLink($lowering->bongkaran ?? '', $mainPhotoUrl);

        $cassingVal = '-';
        if ($lowering->aksesoris_cassing)
            $cassingVal = $valLink($lowering->cassing_quantity, $cassingPhotoUrl);

        $markerTapeVal = '-';
        if ($lowering->aksesoris_marker_tape)
            $markerTapeVal = $valLink($lowering->marker_tape_quantity, $markerTapePhotoUrl);

        $concreteSlabVal = '-';
        if ($lowering->aksesoris_concrete_slab)
            $concreteSlabVal = $valLink($lowering->concrete_slab_quantity, $concreteSlabPhotoUrl);

        $landasanVal = '-';
        if ($lowering->aksesoris_landasan && $lowering->tipe_bongkaran === 'Open Cut') {
            $landasanVal = $valLink($lowering->landasan_quantity, $landasanPhotoUrl);
        }

        $mc100Raw = $lowering->lineNumber->actual_mc100 ?? '';
        $mc100Val = $valLink($mc100Raw, $mainPhotoUrl);

        // NOTE: We assume 2 padding shifts data to start at Column D (Cluster).
        // SPK (Column C) is removed from here and updated manually in syncLowering to ensure placement.
        return [
            '',                                                     // Padding 1
            '',                                                     // Padding 2
            // SPK Removed from array to land Cluster in D
            $clusterName,                                           // D
            $clusterName,                                           // E
            $testPackage,                                           // F (Updated TP-{code})
            $lowering->lineNumber->nama_jalan ?? '',                // G
            $lowering->lineNumber->diameter ?? '',                  // H
            $tanggal,                                               // I
            $lowering->lineNumber->line_number ?? '',               // J
            $lowering->tipe_bongkaran ?? '',                        // K
            $penggelaranVal,                                        // L
            $bongkaranVal,                                          // M
            $lowering->kedalaman_lowering ?? '',                    // N
            $cassingVal,                                            // O
            $markerTapeVal,                                         // P
            $concreteSlabVal,                                       // Q
            ($lowering->tipe_bongkaran === 'Open Cut') ? $landasanVal : ($lowering->tipe_material ?? ''), // R
            '',                                                     // S
            $mc100Val,                                              // T
            '',                                                     // U
            '',                                                     // V
            '',                     // W - Date (for Joint)
            '',                     // X - No Joint
            '',                     // Y - From
            '',                     // Z - To
            '',                     // AA - Fitting
            '',                     // AB - Type
            '',                     // AC - Status
            '',                     // AD - Notes
            '',                     // AE - Keterangan (Empty as requested)
            ''                      // AF
        ];
    }

    private function findRowIndex(string $sheetName, string $lineNumber, ?string $date = null): int
    {
        try {
            // Read columns I (Date) and J (Line Number)
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, "{$sheetName}!I:J");
            $rows = $response->getValues();

            if (empty($rows))
                return -1;

            $searchLN = strtoupper(trim($lineNumber));
            $searchDate = $date ? strtoupper(trim($date)) : null;

            foreach ($rows as $index => $row) {
                // I (Date) -> Index 0 in local row array
                // J (Line Number) -> Index 1 in local row array
                $colI = strtoupper(trim($row[0] ?? ''));
                $colJ = strtoupper(trim($row[1] ?? ''));

                // Check Line Number match (on Col J or I due to shift potential, though J is target)
                // Assuming standard: J is LN.
                // We check if either matches LN (legacy loose check) OR strictly J matches LN?
                // Loose check: `if ($colI === $searchLN || $colJ === $searchLN)`
                // But if we also check Date, we must know which col is Date.
                // Standard mapping: I=Date, J=LN.

                $lnMatch = ($colJ === $searchLN);

                if ($lnMatch) {
                    // If Date is provided, enforce Date match on Col I
                    if ($searchDate) {
                        if ($this->compareIndoDates($colI, $searchDate)) {
                            return $index + 1;
                        }
                        Log::info("Row {$index}: LN Match '{$colJ}' but Date Mismatch. Sheet='{$colI}' vs Search='{$searchDate}'");
                    } else {
                        // If no date requirement, return first LN match
                        return $index + 1;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Find Row Index failed: " . $e->getMessage());
        }
        return -1;
    }

    private function compareIndoDates(string $date1, string $date2): bool
    {
        $d1 = strtoupper(trim($date1));
        $d2 = strtoupper(trim($date2));
        if ($d1 === $d2)
            return true;

        $parts1 = explode('-', $d1);
        $parts2 = explode('-', $d2);

        if (count($parts1) === 3 && count($parts2) === 3) {
            // Check Year (Index 2) - Normalize 2-digit year
            $y1 = (int) $parts1[2];
            $y2 = (int) $parts2[2];
            if ($y1 < 100)
                $y1 += 2000;
            if ($y2 < 100)
                $y2 += 2000;

            if ($y1 !== $y2)
                return false;

            // Check Day (Index 0) - Cast to int to handle "05" vs "5"
            if ((int) $parts1[0] !== (int) $parts2[0])
                return false;

            // Check Month (Index 1) - Normalize
            $m1 = $this->normalizeMonth($parts1[1]);
            $m2 = $this->normalizeMonth($parts2[1]);

            return $m1 > 0 && $m2 > 0 && $m1 === $m2;
        }

        return false;
    }

    private function normalizeMonth(string $monthStr): int
    {
        $map = [
            'JANUARI' => 1,
            'JAN' => 1,
            'FEBRUARI' => 2,
            'FEB' => 2,
            'MARET' => 3,
            'MAR' => 3,
            'APRIL' => 4,
            'APR' => 4,
            'MEI' => 5,
            'MAY' => 5,
            'JUNI' => 6,
            'JUN' => 6,
            'JULI' => 7,
            'JUL' => 7,
            'AGUSTUS' => 8,
            'AGU' => 8,
            'AGT' => 8,
            'AUG' => 8,
            'SEPTEMBER' => 9,
            'SEP' => 9,
            'SEPT' => 9,
            'OKTOBER' => 10,
            'OKT' => 10,
            'OCT' => 10,
            'NOVEMBER' => 11,
            'NOV' => 11,
            'DESEMBER' => 12,
            'DES' => 12,
            'DEC' => 12
        ];

        return $map[$monthStr] ?? 0;
    }

    public function updateLineNumberData(string $lineNumber, string $namaJalan, string $mc100): bool
    {
        try {
            $sheetName = config('services.google_sheets.sheet_name_pe', 'PE');
            $response = $this->service->spreadsheets_values->get(
                $this->spreadsheetId,
                "{$sheetName}!I:AF",
                ['valueRenderOption' => 'FORMULA']
            );
            $rows = $response->getValues();

            if (empty($rows))
                return false;

            Log::info("Update Sync: Scanning " . count($rows) . " rows for LN: {$lineNumber}");

            $requests = [];
            foreach ($rows as $index => $row) {
                $colJ = $row[1] ?? ''; // Column J is Line Number 

                // Only check Column J for strict update (avoid accidental updates if data shifted)
                if (trim($colJ) === $lineNumber) {
                    $sheetRow = $index + 1;
                    $requests[] = ['range' => "{$sheetName}!G{$sheetRow}", 'values' => [[$namaJalan]]];

                    $existingLink = '';
                    $afVal = $row[23] ?? '';
                    if (filter_var($afVal, FILTER_VALIDATE_URL)) {
                        $existingLink = $afVal;
                    } elseif (isset($row[12]) && filter_var($row[12], FILTER_VALIDATE_URL)) {
                        $existingLink = $row[12];
                    } elseif (isset($row[11])) {
                        if (preg_match('/=HYPERLINK\("([^"]+)"/', $row[11], $matches)) {
                            $existingLink = $matches[1];
                        }
                    }

                    $mc100Val = $mc100;
                    if (!empty($existingLink)) {
                        $mc100Val = $this->generateHyperlink($existingLink, $mc100);
                    }
                    $requests[] = ['range' => "{$sheetName}!T{$sheetRow}", 'values' => [[$mc100Val]]];
                }
            }

            if (empty($requests))
                return true;

            $data = [];
            foreach ($requests as $req) {
                $data[] = new Sheets\ValueRange(['range' => $req['range'], 'values' => $req['values']]);
            }

            $this->service->spreadsheets_values->batchUpdate(
                $this->spreadsheetId,
                new Sheets\BatchUpdateValuesRequest(['valueInputOption' => 'USER_ENTERED', 'data' => $data])
            );

            Log::info("Update Sync: Successfully updated " . count($requests) . " cells for LN: {$lineNumber}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update Line Number data: " . $e->getMessage());
            return false;
        }
    }

    public function syncJointRow($joint)
    {
        try {
            $lineNumber = $joint->lineNumber->line_number ?? '';
            Log::info("Joint Sync Debug START: JointID={$joint->id}, LN='{$lineNumber}'");

            // Allow empty Line Number as per user request
            if (empty($lineNumber)) {
                Log::info("Joint Sync Debug: Line Number is empty, proceeding (Column J will be empty).");
            }

            $sheetName = config('services.google_sheets.sheet_name_pe', 'PE');

            // Prepare Data Values
            $dateVal = "'" . $this->formatIndoDate($joint->tanggal_joint);

            // Hyperlink Logic for Joint Number
            $noJointLabel = $joint->nomor_joint ?? '';
            $photoUrl = $this->getPhotoUrl($joint, 'foto_evidence_joint');
            $noJoint = $this->generateHyperlink($photoUrl, $noJointLabel);

            $from = $joint->joint_line_from ?? '';
            $to = $joint->joint_line_to ?? '';

            // Get diameter from joint table (primary) or Line Number (fallback)
            $diameter = $joint->diameter ?? ($joint->lineNumber->diameter ?? '');

            // Special handling for Diameter 90: No fitting (direct pipe-to-pipe joint)
            if ($diameter == '90') {
                $fitting = ''; // Empty for diameter 90
                Log::info("Joint Sync Debug: Diameter 90 detected, Fitting column will be empty (direct pipe-to-pipe joint)");
            } else {
                $fitting = strtoupper($joint->fittingType->nama_fitting ?? $joint->jenis_fitting ?? '-');
            }

            // Use Abbreviation EF/BF (Do not convert to full name)
            $tipePenyambungan = $joint->tipe_penyambungan ?? '-';

            $status = $joint->status_label ?? $joint->status_laporan;

            $comment = '';
            if (str_contains($joint->status_laporan, 'revisi')) {
                $comment = ($joint->cgp_notes ?? '') . ' ' . ($joint->tracer_notes ?? '');
            }
            $ket = $joint->keterangan ?? '';

            $jointValues = [$dateVal, $noJoint, $from, $to, $fitting, $tipePenyambungan, $status, $comment, $ket];
            Log::info("Joint Sync Data for ID {$joint->id} (Diameter: {$diameter}): " . json_encode($jointValues));

            // Prepare Project Info from Cluster Master Data (used for both update and append)
            $cluster = $joint->cluster;
            $spkName = $cluster->spk_name ?? 'City Gas 5 Tahap 2';
            $clusterDisplay = $cluster->sheet_cluster_name ?? $cluster->nama_cluster ?? '';
            $rsSektor = $cluster->rs_sektor ?? $cluster->nama_cluster ?? '';
            $testPackage = $cluster->test_package_code ?? "TP-{$cluster->code_cluster}";
            $jalan = $joint->lokasi_joint ?? '';
            Log::info("Joint Sync Debug: lokasi_joint value = '{$jalan}' for Joint ID {$joint->id}");

            // Scan for Existing Row by Joint Number (Column X / Index 23)
            // Reading A:X to ensure we cover Joint Number column
            $response = $this->service->spreadsheets_values->get(
                $this->spreadsheetId,
                "{$sheetName}!A:X",
                ['valueRenderOption' => 'FORMATTED_VALUE']
            );
            $rows = $response->getValues();
            $rowCount = is_array($rows) ? count($rows) : 0;
            Log::info("Joint Sync Debug: Scanned {$rowCount} rows in {$sheetName}!A:X");

            $targetRow = -1;
            if (!empty($rows)) {
                foreach ($rows as $index => $row) {
                    // Check Joint Number in Column X (Index 23)
                    $colX = isset($row[23]) ? trim((string) $row[23]) : '';

                    // Check string equality (ignoring formula syntax/link if checking raw value vs content?)
                    // The sheet value might be "=HYPERLINK(...)" or just "CODE".
                    // If formatted_value is used, it might be the label "CODE".
                    // However, our $noJoint contains HYPERLINK formula.
                    // To safeguard, we should check against the label if possible, or exact formula match.
                    // Given we write formulas, reading FORMATTED_VALUE returns the Display Text.
                    // But we are constructing the search term as a Formula string.
                    // FIX: It's better to check the LABEL (Part of joint number) against the Cleaned Value from sheet.

                    // IF fetching FORMATTED_VALUE: $colX is the display text (e.g., "KRG-CP001").
                    // IF fetching FORMULA: $colX is "=HYPERLINK(...)".
                    // We requested FORMATTED_VALUE. So $colX is "KRG-CP001".
                    // $noJointLabel is "KRG-CP001".

                    if ($colX === $noJointLabel) {
                        $targetRow = $index + 1; // Sheet is 1-indexed
                        Log::info("Joint Sync Debug: Found Existing Row {$targetRow} by JointNo '{$noJointLabel}'");
                        break;
                    }
                }
            }

            if ($targetRow > 0) {
                // Update Existing Row using Batch Update
                // Update both project info (C-H) and joint data (W-AF)
                $colMap = [
                    // Project Info columns
                    'C' => $spkName,            // SPK
                    'D' => $clusterDisplay,     // Cluster
                    'E' => $rsSektor,           // RS Sektor
                    'F' => $testPackage,        // Test Package
                    'G' => $jalan,              // Nama Jalan
                    'H' => $diameter,           // Diameter
                    // Joint Data columns
                    'W' => $dateVal,           // Date
                    'X' => $noJoint,           // Nomor Joint
                    'Y' => trim($from),        // From
                    'Z' => trim($to),          // To
                    'AA' => trim($fitting),    // Jenis Fitting
                    'AB' => trim($tipePenyambungan),  // Tipe Penyambungan
                    'AC' => '',                // Empty
                    'AD' => trim($status),     // Status
                    'AE' => trim($comment),    // Comment
                    'AF' => trim($ket)         // Keterangan
                ];

                $data = [];
                foreach ($colMap as $col => $val) {
                    $data[] = new Sheets\ValueRange([
                        'range' => "{$sheetName}!{$col}{$targetRow}",
                        'values' => [[$val]]
                    ]);
                }

                $this->service->spreadsheets_values->batchUpdate(
                    $this->spreadsheetId,
                    new Sheets\BatchUpdateValuesRequest([
                        'valueInputOption' => 'USER_ENTERED',
                        'data' => $data
                    ])
                );

                Log::info("Joint Sync Debug: Updated Row {$targetRow} using Batch Update");
                return true;
            } else {
                Log::info("Joint Sync Debug: Appending New Row for LN {$lineNumber}");

                // Build row array - Column A is auto-generated, B is empty, data starts at C
                // Index 0=B (empty), 1=C (SPK), 2=D (Cluster), 3=E (RS Sektor), etc.
                $newRow = [];
                $newRow[0] = '';                        // B: (Empty)
                $newRow[1] = $spkName;                  // C: SPK
                $newRow[2] = $clusterDisplay;           // D: Cluster
                $newRow[3] = $rsSektor;                 // E: RS Sektor
                $newRow[4] = $testPackage;              // F: Test Package
                $newRow[5] = $jalan;                    // G: Nama Jalan
                $newRow[6] = $diameter;                 // H: Diameter
                $newRow[7] = '';                        // I: Line Number (Empty for Joint)

                // Fill empty columns from J to U (index 8-19)
                for ($i = 8; $i <= 21; $i++) {
                    $newRow[$i] = '';
                }

                // Joint Data columns (Index 21-30)
                $newRow[21] = $dateVal;                 // Date
                $newRow[22] = $noJoint;                 // Nomor Joint
                $newRow[23] = trim($from);              // From
                $newRow[24] = trim($to);                // To
                $newRow[25] = trim($fitting);           // Jenis Fitting
                $newRow[26] = trim($tipePenyambungan);  // Tipe Penyambungan
                $newRow[27] = '';                       // (Empty or other data)
                $newRow[28] = trim($status);            // Status
                $newRow[29] = trim($comment);           // Comment
                $newRow[30] = trim($ket);               // Keterangan

                // Ensure array is properly indexed from 0 without gaps
                ksort($newRow);
                // Convert to sequential array (0,1,2,3...) for Google Sheets API
                $newRow = array_values($newRow);

                $appendResult = $this->appendRow($sheetName, $newRow);
                Log::info("Joint Sync Debug: Append Result: " . json_encode($appendResult));
                return (bool) $appendResult;
            }

        } catch (\Exception $e) {
            Log::error("Failed to sync joint row: " . $e->getMessage());
            return false;
        }
    }

    public function syncLowering($lowering, ?string $oldDate = null): bool
    {
        $sheetName = config('services.google_sheets.sheet_name_pe', 'PE');
        $row = $this->formatLoweringRow($lowering);
        $lineNumber = $lowering->lineNumber->line_number ?? '';

        // Determine date to search for: Use old date if provided to handle date changes
        $searchDateRaw = $oldDate ?: $lowering->tanggal_jalur;
        $searchDate = $this->formatIndoDate($searchDateRaw);

        $rowIndex = $this->findRowIndex($sheetName, $lineNumber, $searchDate);

        if ($rowIndex > 0) {
            // Update Existing Row to prevent duplicates
            Log::info("Sync Lowering: Updating existing row {$rowIndex} for {$lineNumber}" . ($oldDate ? " (found via old date)" : ""));
            // Start update from Column B to match Append behavior (which seems to skip A)
            $this->updateRange("{$sheetName}!B{$rowIndex}", [$row]);

            // Ensure SPK is Correct (Force Update C)
            $this->updateRange("{$sheetName}!C{$rowIndex}", [['Cty Gas 5 Tahap 2']]);
            return true;
        } else {
            // Append New Row
            Log::info("Sync Lowering: Appending new row for {$lineNumber} (Search Date: {$searchDate} not found)");
            $newRow = $this->appendRow($sheetName, $row);

            if ($newRow && is_int($newRow)) {
                // Ensure SPK is Correct
                $this->updateRange("{$sheetName}!C{$newRow}", [['Cty Gas 5 Tahap 2']]);
                return true;
            }
            return (bool) $newRow;
        }
    }

    public function syncJointSummary(): bool
    {
        try {
            $joints = \App\Models\JalurJointData::with('lineNumber')->get();
            $summary = [
                'Coupler' => ['63' => 0, '180' => 0],
                'Elbow 90' => ['63' => 0, '180' => 0],
                'Elbow 45' => ['63' => 0, '180' => 0],
                'Equal Tee' => ['63' => 0, '180' => 0],
                'End Cap' => ['63' => 0, '180' => 0],
                'Reducer' => ['63' => 0, '180' => 0],
                'Flange Adaptor' => ['63' => 0, '180' => 0],
                'Valve' => ['63' => 0, '180' => 0],
            ];

            foreach ($joints as $joint) {
                $type = $this->normalizeJointType($joint->jenis_fitting);
                $diameter = $joint->lineNumber->diameter ?? '63';
                if ($diameter != '180')
                    $diameter = '63';

                if (isset($summary[$type])) {
                    $summary[$type][$diameter]++;
                }
            }

            $order = ['Coupler', 'Elbow 90', 'Elbow 45', 'Equal Tee', 'End Cap', 'Reducer', 'Flange Adaptor', 'Valve'];
            $values = [];
            foreach ($order as $type) {
                $values[] = [$summary[$type]['63'], $summary[$type]['180']];
            }

            $sheetName = config('services.google_sheets.sheet_name_pe', 'PE');
            return $this->updateRange("{$sheetName}!H4:I11", $values);

        } catch (\Exception $e) {
            Log::error("Failed to sync joint summary: " . $e->getMessage());
            return false;
        }
    }

    private function normalizeJointType($dbType)
    {
        $map = [
            'coupler' => 'Coupler',
            'elbow_90' => 'Elbow 90',
            'elbow_45' => 'Elbow 45',
            'equal_tee' => 'Equal Tee',
            'end_cap' => 'End Cap',
            'reducer' => 'Reducer',
            'flange_adaptor' => 'Flange Adaptor',
            'valve' => 'Valve',
            'Elbow 90 Derajat' => 'Elbow 90',
            'Elbow 45 Derajat' => 'Elbow 45',
            'Tee' => 'Equal Tee',
            'Equal Tee' => 'Equal Tee',
            'Cap' => 'End Cap',
            'Dop' => 'End Cap',
        ];

        if (isset($map[$dbType]))
            return $map[$dbType];

        foreach ($map as $key => $val) {
            if (stripos($dbType, $key) !== false)
                return $val;
        }
        return 'Coupler';
    }

    public function getCalonPelangganData(): array
    {
        try {
            $sheetName = config('services.google_sheets.sheet_name_calon_pelanggan', 'Pilot KSM');
            Log::info("Fetching Calon Pelanggan data from sheet: {$sheetName}");

            // Read all data
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, "{$sheetName}!A:Z");
            $rows = $response->getValues();

            if (empty($rows)) {
                Log::warning("Google Sheet returned empty rows");
                return [];
            }

            Log::info("Google Sheet returned " . count($rows) . " rows (including header)");
            Log::info("Raw first row: " . json_encode($rows[0] ?? []));
            Log::info("Raw second row: " . json_encode($rows[1] ?? []));
            Log::info("Raw third row (should be headers): " . json_encode($rows[2] ?? []));

            // Skip row 1 (empty row before title)
            array_shift($rows);

            // Skip row 2 (merged title row: "DATA CALON PELANGGAN")
            array_shift($rows);

            // Extract headers (row 3 - actual column headers: No, Area, Nama, etc.)
            $rawHeaders = array_shift($rows);
            if (empty($rawHeaders)) {
                Log::warning("Header row is empty");
                return [];
            }

            $headers = array_map(function ($h) {
                // Normalize: lowercase, trim, and replace newlines with space
                $normalized = strtolower(trim($h ?? ''));
                $normalized = preg_replace('/[\r\n]+/', ' ', $normalized);
                return $normalized;
            }, $rawHeaders);

            // Skip filter row (row 4 with counts like "140")
            array_shift($rows);

            // Simple Mapping: Header Label => DB Column
            // Headers are normalized: lowercase, trimmed, newlines replaced with spaces
            $mapping = [
                // Primary columns from sheet - 'id reff' is the actual customer ID
                'id reff' => 'reff_id_pelanggan',
                'nama' => 'nama_pelanggan',
                'nomor kartu identitas' => 'no_ktp',
                'nomor kartu' => 'no_ktp',
                'nomor ponsel' => 'no_telepon',
                'alamat' => 'alamat',
                'rt' => 'rt',
                'rw' => 'rw',
                'id kota/kabupaten' => 'kota_kabupaten',
                'id kecamatan' => 'kecamatan',
                'id kelurahan' => 'kelurahan',
                'padukuhan' => 'padukuhan',
                'jenis pelanggan' => 'jenis_pelanggan',
                'penetrasi / pengembangan' => 'jenis_pelanggan',
                'keterangan' => 'keterangan',
                'email' => 'email',
                // Legacy/alternate mappings
                'reff id' => 'reff_id_pelanggan',
                'reff_id' => 'reff_id_pelanggan',
                'id' => 'reff_id_pelanggan',
                'nama pelanggan' => 'nama_pelanggan',
                'no telepon' => 'no_telepon',
                'telepon' => 'no_telepon',
                'no hp' => 'no_telepon',
                'kelurahan' => 'kelurahan',
                'kecamatan' => 'kecamatan',
                'kota' => 'kota_kabupaten',
                'kabupaten' => 'kota_kabupaten',
                'jenis' => 'jenis_pelanggan'
            ];

            // Build index map: DB Column => Index in Row
            $colIndexMap = [];
            foreach ($headers as $index => $header) {
                if (isset($mapping[$header])) {
                    $colIndexMap[$mapping[$header]] = $index;
                }
            }

            Log::info("Sheet headers: " . json_encode($headers));
            Log::info("Mapped columns: " . json_encode($colIndexMap));

            // Process Rows
            $data = [];
            foreach ($rows as $row) {
                // Skip empty rows
                if (empty($row))
                    continue;

                $item = [];
                foreach ($colIndexMap as $dbCol => $index) {
                    $item[$dbCol] = $row[$index] ?? null;
                }

                // Basic validation: Must have Reff ID or Name
                if (!empty($item['reff_id_pelanggan']) || !empty($item['nama_pelanggan'])) {
                    $data[] = $item;
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Failed to fetch Calon Pelanggan data: " . $e->getMessage());
            return [];
        }
    }
}
