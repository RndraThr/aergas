<?php

namespace App\Imports;

use App\Models\JalurJointData;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Models\JalurLineNumber;
use App\Models\PhotoApproval;
use App\Services\GoogleDriveService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JalurJointImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private bool   $dryRun;
    private bool   $forceUpdate;
    private bool   $allowRecall;
    private array  $results    = [];
    private string $filePath;
    private array  $hyperlinks = [];
    private array  $rowMapping = [];    // "joint_number|date" => excel_row
    private array  $seenInFile = [];    // joint_number => first_row (duplicate guard)

    private GoogleDriveService $googleDriveService;

    /**
     * @param bool   $dryRun      true = preview only, no DB writes
     * @param string $filePath    absolute path to the uploaded Excel file
     * @param bool   $forceUpdate true = overwrite existing draft fields; false = fill empty only
     * @param bool   $allowRecall true = approved/rejected records with krusial changes get recalled to draft
     */
    public function __construct(bool $dryRun = false, string $filePath = '', bool $forceUpdate = false, bool $allowRecall = false)
    {
        $this->dryRun      = $dryRun;
        $this->filePath    = $filePath;
        $this->forceUpdate = $forceUpdate;
        $this->allowRecall = $allowRecall;

        $this->googleDriveService = app(GoogleDriveService::class);

        if ($filePath && file_exists($filePath)) {
            $this->extractAllHyperlinks();
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  MAIN COLLECTION LOOP
    // ──────────────────────────────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $data) {
            // Default row number (before mapping is resolved)
            $excelRowNumber = $index + 2;

            try {
                // Skip rows with no joint_number
                if (empty($data['joint_number']) || trim($data['joint_number']) === '') {
                    continue;
                }

                $jointNumber = trim($data['joint_number']);

                // Resolve accurate Excel row via pre-built mapping
                $tanggal = $data['tanggal_joint'] ?? null;
                if ($jointNumber && $tanggal) {
                    $tanggalStr = is_numeric($tanggal)
                        ? $this->excelDateToString($tanggal)
                        : (string) $tanggal;

                    $mapKey = $jointNumber . '|' . $tanggalStr;
                    if (isset($this->rowMapping[$mapKey])) {
                        $excelRowNumber = $this->rowMapping[$mapKey];
                    }
                }

                // ── Duplicate within file ─────────────────────────────────
                // Key: joint_number + tanggal + joint_line_from + joint_line_to
                $lineFrom  = trim($data['joint_line_from'] ?? '');
                $lineTo    = trim($data['joint_line_to']   ?? '');
                $dupKey    = implode('|', [$jointNumber, $tanggalStr ?? '', $lineFrom, $lineTo]);

                if (isset($this->seenInFile[$dupKey])) {
                    $this->results[] = [
                        'row'       => $excelRowNumber,
                        'status'    => 'duplicate_in_file',
                        'data'      => ['joint_number' => $jointNumber],
                        'message'   => "Duplikat dalam file — kombinasi joint '{$jointNumber}' + tanggal + line from/to sudah ada di baris {$this->seenInFile[$dupKey]}",
                        'first_row' => $this->seenInFile[$dupKey],
                    ];
                    continue;
                }
                $this->seenInFile[$dupKey] = $excelRowNumber;

                $this->results[] = $this->processRow($data, $excelRowNumber);

            } catch (\Exception $e) {
                $this->results[] = [
                    'row'     => $excelRowNumber,
                    'status'  => 'error',
                    'data'    => ['joint_number' => $data['joint_number'] ?? '-'],
                    'message' => $e->getMessage(),
                ];

                Log::error('Joint import row error', [
                    'row'   => $excelRowNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  ROW PROCESSING
    // ──────────────────────────────────────────────────────────────────────

    private function processRow(Collection $data, int $row): array
    {
        // 1. Parse joint_number
        $jointNumber = trim($data['joint_number']);
        $parsed = $this->parseJointNumber($jointNumber);

        if (!$parsed) {
            throw new \Exception(
                "Format joint number tidak valid. "
                . "Format 1: {CLUSTER}-{FITTING}{CODE} (mis. KRG-CP001). "
                . "Format 2 (dia.180): {TIPE}.{KODE} (mis. BF.05)"
            );
        }

        [$clusterCode, $fittingCode, $jointCode] = $parsed;

        // 2. Resolve cluster
        $clusterCode = $this->resolveClusterCode($clusterCode, $data);
        $cluster = JalurCluster::where('code_cluster', $clusterCode)->first();
        if (!$cluster) {
            throw new \Exception("Cluster dengan code '{$clusterCode}' tidak ditemukan di database");
        }

        // 3. Resolve fitting type
        $fittingType = $this->resolveFittingType($fittingCode, $data);
        // After resolution, normalise fittingCode
        $fittingCode = $fittingType ? $fittingType->code_fitting : 'BF';

        // 4. Required-field validation
        $this->validateRequiredFields($data);

        // 5. Convert tanggal_joint
        $tanggalJoint = $this->parseTanggal($data['tanggal_joint']);

        // 6. Validate diameter
        $diameter = (string) $data['diameter'];
        if (!in_array($diameter, ['63', '90', '110', '160', '180', '200'])) {
            throw new \Exception("Diameter tidak valid. Pilihan: 63, 90, 110, 160, 180, 200");
        }

        // 7. Validate line numbers
        $jointLineFrom     = trim($data['joint_line_from']);
        $jointLineTo       = trim($data['joint_line_to']);
        $jointLineOptional = !empty($data['joint_line_optional']) ? trim($data['joint_line_optional']) : null;

        $lineFrom = $this->validateLine($jointLineFrom, $cluster, 'joint_line_from');
        $lineTo   = $this->validateLine($jointLineTo,   $cluster, 'joint_line_to');

        // Cross-diameter warning
        if ($lineFrom && $lineTo && $lineFrom->diameter != $lineTo->diameter) {
            if ($fittingType && stripos($fittingType->code_fitting, 'RD') === false) {
                Log::warning("Joint {$jointNumber}: diameter berbeda ({$lineFrom->diameter} vs {$lineTo->diameter}) tapi fitting bukan RD ({$fittingType->code_fitting})");
            }
        }

        // Equal Tee optional line
        if ($fittingType && $fittingType->code_fitting === 'TE' && !empty($jointLineOptional)) {
            $this->validateLine($jointLineOptional, $cluster, 'joint_line_optional');
        }

        // 8. Validate tipe_penyambungan
        $tipePenyambungan = strtoupper(trim($data['tipe_penyambungan']));
        if (!in_array($tipePenyambungan, ['EF', 'BF'])) {
            throw new \Exception("Tipe penyambungan harus 'EF' atau 'BF'");
        }

        // 9. Foto hyperlink (column A)
        $fotoHyperlink = $this->getHyperlink($row, 'A');

        // 10. Build canonical newData payload
        $newData = [
            'cluster_id'          => $cluster->id,
            'cluster_name'        => $cluster->nama_cluster,
            'fitting_type_id'     => $fittingType?->id,
            'fitting_name'        => $fittingType?->nama_fitting ?? '-',
            'joint_code'          => $jointCode,
            'tanggal_joint'       => $tanggalJoint,
            'joint_line_from'     => $jointLineFrom,
            'joint_line_to'       => $jointLineTo,
            'joint_line_optional' => $jointLineOptional,
            'tipe_penyambungan'   => $tipePenyambungan,
            'keterangan'          => $data['keterangan'] ?? null,
            'foto_hyperlink'      => $fotoHyperlink,
        ];

        // 11. Existing or new?
        // Lookup pakai composite key: nomor_joint + tanggal_joint + joint_line_from + joint_line_to
        $matchingRecords = JalurJointData::where('nomor_joint', $jointNumber)
            ->where('tanggal_joint', $tanggalJoint)
            ->where('joint_line_from', $jointLineFrom)
            ->where('joint_line_to', $jointLineTo)
            ->get();

        // Deteksi duplikat di DB (lebih dari 1 record dengan composite key yang sama)
        if ($matchingRecords->count() > 1) {
            return [
                'row'     => $row,
                'status'  => 'db_duplicate',
                'data'    => [
                    'joint_number'    => $jointNumber,
                    'cluster'         => $cluster->nama_cluster,
                    'tanggal_joint'   => $tanggalJoint,
                    'joint_line_from' => $jointLineFrom,
                    'joint_line_to'   => $jointLineTo,
                    'duplicate_ids'   => $matchingRecords->pluck('id')->toArray(),
                    'count'           => $matchingRecords->count(),
                ],
                'message' => "Ditemukan {$matchingRecords->count()} record duplikat di database untuk joint ini. Selesaikan duplikat terlebih dahulu melalui halaman Kelola Duplikat.",
            ];
        }

        $existing = $matchingRecords->first();

        if ($existing) {
            return $this->handleExisting($existing, $jointNumber, $cluster, $fittingType, $newData, $row);
        }

        return $this->handleNew($jointNumber, $cluster, $fittingType, $newData, $row);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HANDLE EXISTING JOINT
    // ──────────────────────────────────────────────────────────────────────

    private function handleExisting($existing, string $jointNumber, $cluster, $fittingType, array $newData, int $row): array
    {
        $isApproved = $this->isApproved($existing);

        $diff         = $this->computeDiff($existing, $newData);
        $photoChanged = $this->hasPhotoChange($existing, $newData['foto_hyperlink']);

        $hasKrusial    = collect($diff)->where('krusial', true)->isNotEmpty();
        $hasNonKrusial = collect($diff)->where('krusial', false)->isNotEmpty();
        $noDiff        = empty($diff) && !$photoChanged;

        // ── No changes at all ────────────────────────────────────────────
        if ($noDiff) {
            return $this->buildResult($row, 'skip_no_change', $existing, $jointNumber, $cluster, $fittingType, $newData, [], false, 'Data sama persis, tidak ada perubahan');
        }

        // ── APPROVED / REJECTED record ───────────────────────────────────
        if ($isApproved) {
            if ($hasKrusial || $photoChanged) {
                if (!$this->allowRecall) {
                    // Protected → skip
                    return $this->buildResult($row, 'skip_approved', $existing, $jointNumber, $cluster, $fittingType, $newData, $diff, $photoChanged,
                        "Data sudah {$existing->status_laporan}. Aktifkan 'Allow Recall' untuk mengubah data krusial.");
                }

                // Recall → reset to draft + overwrite all
                if (!$this->dryRun) {
                    $this->applyUpdate($existing, $newData, [
                        'cluster_id', 'fitting_type_id', 'joint_code', 'tanggal_joint',
                        'joint_line_from', 'joint_line_to', 'joint_line_optional',
                        'tipe_penyambungan', 'keterangan',
                    ], resetStatus: true);

                    if ($photoChanged && !empty($newData['foto_hyperlink'])) {
                        $this->replacePhoto($existing, $newData['foto_hyperlink']);
                    }
                }

                // Mark all diff as will_apply = true (recall forces overwrite)
                $diff = array_map(fn($d) => array_merge($d, ['will_apply' => true]), $diff);

                return $this->buildResult($row, 'recall', $existing, $jointNumber, $cluster, $fittingType, $newData, $diff, $photoChanged,
                    "Di-recall dari '{$existing->status_laporan}' → draft dan diupdate");
            }

            // Only non-krusial changed → safe update without recall
            if ($hasNonKrusial && !$this->dryRun) {
                $this->applyUpdate($existing, $newData, ['keterangan'], resetStatus: false);
            }

            return $this->buildResult($row, 'update', $existing, $jointNumber, $cluster, $fittingType, $newData, $diff, false,
                "Non-krusial diupdate (status tetap: {$existing->status_laporan})");
        }

        // ── DRAFT record ─────────────────────────────────────────────────
        // Determine what will_apply for each diff field
        $diff = array_map(function ($d) use ($existing) {
            $oldVal = (string) ($existing->{$d['field']} instanceof \Carbon\Carbon
                ? $existing->{$d['field']}->format('Y-m-d')
                : ($existing->{$d['field']} ?? ''));
            $oldIsEmpty     = $oldVal === '' || $oldVal === '0';
            $d['will_apply'] = $this->forceUpdate || $oldIsEmpty;
            return $d;
        }, $diff);

        $photoWillApply = $photoChanged && $this->forceUpdate;

        $willApplyCount = collect($diff)->where('will_apply', true)->count();
        if ($willApplyCount === 0 && !$photoWillApply) {
            return $this->buildResult($row, 'skip_no_change', $existing, $jointNumber, $cluster, $fittingType, $newData, $diff, false,
                'Tidak ada perubahan yang akan diterapkan (Force Update OFF, semua field sudah terisi)');
        }

        if (!$this->dryRun) {
            $fieldsToApply = collect($diff)->where('will_apply', true)->pluck('field')->toArray();
            if (!empty($fieldsToApply)) {
                $this->applyUpdate($existing, $newData, $fieldsToApply, resetStatus: false);
            }
            if ($photoWillApply) {
                $this->replacePhoto($existing, $newData['foto_hyperlink']);
            }
        }

        return $this->buildResult($row, 'update', $existing, $jointNumber, $cluster, $fittingType, $newData, $diff, $photoWillApply,
            'Updated field yang berubah (status: draft)');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HANDLE NEW JOINT
    // ──────────────────────────────────────────────────────────────────────

    private function handleNew(string $jointNumber, $cluster, $fittingType, array $newData, int $row): array
    {
        if (!$this->dryRun) {
            DB::beginTransaction();
            try {
                $joint = JalurJointData::create([
                    'nomor_joint'         => $jointNumber,
                    'cluster_id'          => $newData['cluster_id'],
                    'fitting_type_id'     => $newData['fitting_type_id'],
                    'joint_code'          => $newData['joint_code'],
                    'tanggal_joint'       => $newData['tanggal_joint'],
                    'joint_line_from'     => $newData['joint_line_from'],
                    'joint_line_to'       => $newData['joint_line_to'],
                    'joint_line_optional' => $newData['joint_line_optional'],
                    'tipe_penyambungan'   => $newData['tipe_penyambungan'],
                    'keterangan'          => $newData['keterangan'],
                    'status_laporan'      => 'draft',
                    'created_by'          => Auth::id(),
                    'updated_by'          => Auth::id(),
                ]);

                if (!empty($newData['foto_hyperlink'])) {
                    $this->copyPhotoFromDrive($joint, $newData['foto_hyperlink'], 'foto_evidence_joint');
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return [
            'row'     => $row,
            'status'  => 'new',
            'data'    => [
                'joint_number'    => $jointNumber,
                'cluster'         => $cluster->nama_cluster,
                'fitting_type'    => $fittingType?->nama_fitting ?? '-',
                'tanggal_joint'   => $newData['tanggal_joint'],
                'joint_line_from' => $newData['joint_line_from'],
                'joint_line_to'   => $newData['joint_line_to'],
                'tipe_penyambungan'=> $newData['tipe_penyambungan'],
                'foto_hyperlink'  => $newData['foto_hyperlink'],
            ],
            'diff'    => [],
            'message' => 'Baru — akan dibuat',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DIFF COMPUTATION
    //  Compares existing DB record to new Excel data.
    //  Returns array of field entries with will_apply flag.
    //  NOTE: will_apply for draft records is finalised in handleExisting.
    // ──────────────────────────────────────────────────────────────────────

    private function computeDiff($existing, array $newData): array
    {
        $isApproved = $this->isApproved($existing);

        $fieldDefs = [
            'cluster_id'          => ['label' => 'Cluster',           'krusial' => true],
            'fitting_type_id'     => ['label' => 'Fitting Type',      'krusial' => true],
            'tanggal_joint'       => ['label' => 'Tanggal Joint',     'krusial' => true],
            'joint_line_from'     => ['label' => 'Line From',         'krusial' => true],
            'joint_line_to'       => ['label' => 'Line To',           'krusial' => true],
            'joint_line_optional' => ['label' => 'Line Optional',     'krusial' => true],
            'tipe_penyambungan'   => ['label' => 'Tipe Penyambungan', 'krusial' => true],
            'keterangan'          => ['label' => 'Keterangan',        'krusial' => false],
        ];

        // Human-readable display values for ID fields
        $displayOld = [
            'cluster_id'      => optional($existing->cluster)->nama_cluster ?? '(kosong)',
            'fitting_type_id' => optional($existing->fittingType)->nama_fitting ?? '(kosong)',
        ];
        $displayNew = [
            'cluster_id'      => $newData['cluster_name'],
            'fitting_type_id' => $newData['fitting_name'],
        ];

        $diff = [];

        foreach ($fieldDefs as $field => $cfg) {
            $rawOld = $existing->$field;

            // Normalise Carbon to string
            if ($rawOld instanceof \Carbon\Carbon) {
                $rawOld = $rawOld->format('Y-m-d');
            }

            $oldStr = (string) ($rawOld ?? '');
            $newStr = (string) ($newData[$field] ?? '');

            if ($oldStr === $newStr) {
                continue; // no change
            }

            $oldIsEmpty = $oldStr === '' || $oldStr === '0';

            // Preliminary will_apply (may be overridden in handleExisting for draft)
            if ($isApproved) {
                $willApply = $cfg['krusial'] ? $this->allowRecall : true;
            } else {
                $willApply = $this->forceUpdate || $oldIsEmpty;
            }

            $diff[] = [
                'field'      => $field,
                'label'      => $cfg['label'],
                'old'        => $displayOld[$field] ?? $oldStr,
                'new'        => $displayNew[$field] ?? $newStr,
                'krusial'    => $cfg['krusial'],
                'will_apply' => $willApply,
            ];
        }

        return $diff;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function isApproved($joint): bool
    {
        return in_array($joint->status_laporan, [
            'tracer_approved', 'cgp_approved',
            'tracer_rejected', 'cgp_rejected',
        ]);
    }

    private function hasPhotoChange($joint, ?string $newLink): bool
    {
        if (empty($newLink)) return false;

        return PhotoApproval::where('module_name', 'jalur_joint')
            ->where('module_record_id', $joint->id)
            ->where('photo_field_name', 'foto_evidence_joint')
            ->exists();
    }

    private function applyUpdate($joint, array $newData, array $fields, bool $resetStatus): void
    {
        $payload = ['updated_by' => Auth::id()];
        foreach ($fields as $f) {
            if (array_key_exists($f, $newData)) {
                $payload[$f] = $newData[$f];
            }
        }
        if ($resetStatus) {
            $payload['status_laporan'] = 'draft';
        }
        $joint->update($payload);
    }

    private function replacePhoto($joint, string $newLink): void
    {
        PhotoApproval::where('module_name', 'jalur_joint')
            ->where('module_record_id', $joint->id)
            ->delete();

        $this->copyPhotoFromDrive($joint, $newLink, 'foto_evidence_joint');
    }

    private function copyPhotoFromDrive(JalurJointData $joint, string $driveLink, string $fieldName): void
    {
        try {
            $clusterSlug = \Illuminate\Support\Str::slug($joint->cluster->nama_cluster, '_');
            $dateFolder  = $joint->tanggal_joint->format('Y-m-d');
            $customPath  = "jalur_joint/{$clusterSlug}/{$joint->nomor_joint}/{$dateFolder}";
            $fileName    = "{$fieldName}_" . now()->format('YmdHis');

            $result = $this->googleDriveService->copyFromDriveLink($driveLink, $customPath, $fileName);

            PhotoApproval::create([
                'module_name'      => 'jalur_joint',
                'module_record_id' => $joint->id,
                'photo_field_name' => $fieldName,
                'photo_url'        => $result['url'],
                'drive_file_id'    => $result['id'] ?? null,
                'photo_status'     => 'tracer_pending',
                'uploaded_by'      => Auth::id(),
                'uploaded_at'      => now(),
            ]);

            Log::info('Joint photo copied from Drive', [
                'joint_id'  => $joint->id,
                'field'     => $fieldName,
                'drive_link'=> $driveLink,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to copy joint photo from Drive', [
                'joint_id' => $joint->id,
                'error'    => $e->getMessage(),
            ]);
            throw new \Exception("Gagal copy foto {$fieldName} dari Google Drive: " . $e->getMessage());
        }
    }

    private function buildResult(
        int    $row,
        string $status,
        $existing,
        string $jointNumber,
        $cluster,
        $fittingType,
        array  $newData,
        array  $diff,
        bool   $photoChanged,
        string $message
    ): array {
        return [
            'row'             => $row,
            'status'          => $status,
            'data'            => [
                'joint_number'     => $jointNumber,
                'cluster'          => $cluster->nama_cluster,
                'fitting_type'     => $fittingType?->nama_fitting ?? '-',
                'tanggal_joint'    => $newData['tanggal_joint'],
                'joint_line_from'  => $newData['joint_line_from'],
                'joint_line_to'    => $newData['joint_line_to'],
                'tipe_penyambungan'=> $newData['tipe_penyambungan'],
                'existing_status'  => $existing->status_laporan,
                'foto_hyperlink'   => $newData['foto_hyperlink'],
            ],
            'diff'            => $diff,
            'photo_changed'   => $photoChanged,
            'previous_status' => $existing->status_laporan,
            'message'         => $message,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  VALIDATION HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function parseJointNumber(string $jointNumber): ?array
    {
        // Format 1: {CLUSTER}-{FITTING}{CODE}  — mis. KRG-CP001, GDK-EL90124, KRG-TE002
        if (preg_match('/^([A-Z0-9\-]+)-([A-Z]+\d*)(\d{3,})$/', $jointNumber, $m)) {
            return [$m[1], $m[2], $m[3]];
        }

        // Format 2 (diameter 180): {TIPE}.{KODE} atau {TIPE}{KODE} — mis. BF.05, EF.010
        if (preg_match('/^([A-Z]+)[\.\-]?(\d{1,})$/', $jointNumber, $m)) {
            return ['NONE', 'DIAMETER_180', $m[0]];
        }

        return null;
    }

    private function resolveClusterCode(string $clusterCode, Collection $data): string
    {
        if ($clusterCode !== 'NONE') return $clusterCode;

        if (!empty($data['cluster'])) {
            return strtoupper(trim($data['cluster']));
        }

        $lineFrom = trim($data['joint_line_from'] ?? '');
        $lineTo   = trim($data['joint_line_to'] ?? '');
        $regex    = '/^\d+\s*-\s*([A-Za-z0-9\-\s]+?)\s*-\s*LN\d+$/i';

        if (preg_match($regex, $lineFrom, $m)) return strtoupper(trim($m[1]));
        if (preg_match($regex, $lineTo,   $m)) return strtoupper(trim($m[1]));

        $jointNumber = $data['joint_number'] ?? '?';
        throw new \Exception(
            "Gagal mendeteksi Cluster.\nJoint: {$jointNumber}.\n"
            . "Line From: '{$lineFrom}'. Line To: '{$lineTo}'.\n"
            . "Pastikan format Line: {DIAMETER}-{CLUSTER}-LN{NUMBER} (mis. 180-KRG-LN001), "
            . "atau isi kolom 'cluster' di Excel."
        );
    }

    private function resolveFittingType(string $fittingCode, Collection $data): ?JalurFittingType
    {
        if ($fittingCode !== 'DIAMETER_180') {
            $ft = JalurFittingType::where('code_fitting', $fittingCode)->first();
            if (!$ft) throw new \Exception("Fitting Type '{$fittingCode}' tidak ditemukan di database");
            return $ft;
        }

        // Diameter 180 format: fitting type from column or default
        $fromColumn = !empty($data['fitting_type']) ? trim($data['fitting_type']) : null;
        if ($fromColumn) {
            $ft = JalurFittingType::where('code_fitting', $fromColumn)->first();
            if (!$ft) throw new \Exception("Fitting Type '{$fromColumn}' tidak ditemukan di database");
            return $ft;
        }

        $tipe = strtoupper(trim($data['tipe_penyambungan'] ?? ''));
        if ($tipe === 'BF') return null; // pipe-to-pipe, no fitting

        $ft = JalurFittingType::where('code_fitting', 'CP')->first();
        if (!$ft) throw new \Exception("Fitting type default 'CP' tidak ditemukan. Tambah kolom 'fitting_type' di Excel.");
        return $ft;
    }

    private function validateLine(string $lineNumber, $cluster, string $fieldName): ?JalurLineNumber
    {
        if (strtoupper($lineNumber) === 'EXISTING') return null;

        $line = JalurLineNumber::where('line_number', $lineNumber)
            ->where('cluster_id', $cluster->id)
            ->first();

        if (!$line) {
            throw new \Exception("Line '{$lineNumber}' tidak ditemukan di cluster {$cluster->nama_cluster} ({$fieldName})");
        }

        return $line;
    }

    private function validateRequiredFields(Collection $data): void
    {
        $required = [
            'joint_number'      => 'Nomor Joint',
            'tanggal_joint'     => 'Tanggal Joint',
            'diameter'          => 'Diameter',
            'joint_line_from'   => 'Joint Line From',
            'joint_line_to'     => 'Joint Line To',
            'tipe_penyambungan' => 'Tipe Penyambungan',
        ];

        foreach ($required as $field => $label) {
            if (empty($data[$field]) || trim((string) $data[$field]) === '') {
                throw new \Exception("Field '{$label}' wajib diisi");
            }
        }
    }

    private function parseTanggal($value): string
    {
        if (is_numeric($value) && $value > 0) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }
        return (string) $value;
    }

    private function excelDateToString($value): string
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HYPERLINK EXTRACTION
    // ──────────────────────────────────────────────────────────────────────

    private function extractAllHyperlinks(): void
    {
        try {
            $spreadsheet     = IOFactory::load($this->filePath);
            $worksheet       = $spreadsheet->getActiveSheet();
            $highestRow      = $worksheet->getHighestRow();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $worksheet->getHighestColumn()
            );

            for ($row = 2; $row <= $highestRow; $row++) {
                $jointNumber = $worksheet->getCell('A' . $row)->getValue();
                $tanggal     = $worksheet->getCell('B' . $row)->getValue();

                if ($tanggal && is_numeric($tanggal)) {
                    $tanggal = $this->excelDateToString($tanggal);
                }

                if ($jointNumber && $tanggal) {
                    $this->rowMapping[$jointNumber . '|' . $tanggal] = $row;
                }

                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                    $cell  = $worksheet->getCell($coord);

                    if ($cell->getHyperlink() && $cell->getHyperlink()->getUrl()) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $this->hyperlinks[$row][$colLetter] = $cell->getHyperlink()->getUrl();
                    }
                }
            }

            Log::info('Joint hyperlinks extracted', ['count' => array_sum(array_map('count', $this->hyperlinks))]);
        } catch (\Exception $e) {
            Log::error('Failed to extract joint hyperlinks', [
                'file'  => $this->filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getHyperlink(int $excelRow, string $column): ?string
    {
        return $this->hyperlinks[$excelRow][$column] ?? null;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ──────────────────────────────────────────────────────────────────────

    public function getResults(): array
    {
        return $this->results;
    }

    public function getSummary(): array
    {
        $grouped = collect($this->results)->groupBy('status');

        return [
            'total_rows'        => count($this->results),
            'new'               => $grouped->get('new',               collect())->count(),
            'update'            => $grouped->get('update',            collect())->count(),
            'skip_no_change'    => $grouped->get('skip_no_change',    collect())->count(),
            'skip_approved'     => $grouped->get('skip_approved',     collect())->count(),
            'recall'            => $grouped->get('recall',            collect())->count(),
            'error'             => $grouped->get('error',             collect())->count(),
            'duplicate_in_file' => $grouped->get('duplicate_in_file', collect())->count(),
            'db_duplicate'      => $grouped->get('db_duplicate',      collect())->count(),
            'force_update'      => $this->forceUpdate,
            'allow_recall'      => $this->allowRecall,
            'details'           => $this->results,
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
