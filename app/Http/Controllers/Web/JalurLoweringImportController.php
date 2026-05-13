<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurLoweringImport;
use App\Exports\JalurLoweringTemplateExport;
use App\Models\JalurLoweringData;
use App\Models\JalurLineNumber;
use App\Models\PhotoApproval;
use App\Services\GoogleSheetsService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class JalurLoweringImportController extends Controller
{
    public function index()
    {
        return view('jalur.lowering.import');
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new JalurLoweringTemplateExport(),
            'template_jalur_lowering_' . date('Y-m-d') . '.xlsx'
        );
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'force_update' => 'nullable|boolean',
            'allow_recall' => 'nullable|boolean',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            $forceUpdate = $request->boolean('force_update');
            $allowRecall = $request->boolean('allow_recall');

            $tempDir = storage_path('app/temp-imports');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $destinationPath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;

            if (!copy($filePath, $destinationPath) || !file_exists($destinationPath)) {
                throw new \Exception('Gagal menyimpan file temporary. Silakan coba lagi.');
            }

            $import = new JalurLoweringImport(true, $destinationPath, $forceUpdate, $allowRecall);
            Excel::import($import, $destinationPath);

            return view('jalur.lowering.import-preview', [
                'summary' => $import->getSummary(),
                'fileName' => $file->getClientOriginalName(),
                'tempFilePath' => 'temp-imports/' . $tempFileName,
                'forceUpdate' => $forceUpdate,
                'allowRecall' => $allowRecall,
            ]);
        } catch (\Exception $e) {
            Log::error('Import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Gagal memproses file: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function import(Request $request)
    {
        $fromPreview = $request->has('temp_file_path');
        $forceUpdate = $request->boolean('force_update');
        $allowRecall = $request->boolean('allow_recall');

        if ($fromPreview) {
            $tempFilePath = storage_path('app/' . $request->input('temp_file_path'));

            if (!file_exists($tempFilePath)) {
                return redirect()
                    ->route('jalur.lowering.import.index')
                    ->with('error', 'File temporary tidak ditemukan. Silakan upload ulang.');
            }

            $filePath = $tempFilePath;
        } else {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
                'force_update' => 'nullable|boolean',
                'allow_recall' => 'nullable|boolean',
            ]);

            $filePath = $request->file('file')->getRealPath();
        }

        try {
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            DB::connection()->disableQueryLog();

            $import = new JalurLoweringImport(false, $filePath, $forceUpdate, $allowRecall);
            Excel::import($import, $filePath);

            if ($fromPreview && isset($tempFilePath) && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            $summary = $import->getSummary();

            $parts = [];
            if ($summary['new'] > 0)               $parts[] = "{$summary['new']} baru";
            if ($summary['update'] > 0)            $parts[] = "{$summary['update']} diupdate";
            if ($summary['recall'] > 0)            $parts[] = "{$summary['recall']} di-recall";
            if ($summary['skip_no_change'] > 0)    $parts[] = "{$summary['skip_no_change']} tidak berubah";
            if ($summary['skip_approved'] > 0)     $parts[] = "{$summary['skip_approved']} approved (dilindungi)";
            if ($summary['duplicate_in_file'] > 0) $parts[] = "{$summary['duplicate_in_file']} duplikat di file";
            if ($summary['error'] > 0)             $parts[] = "{$summary['error']} error";

            $message = 'Import selesai: ' . (empty($parts) ? 'tidak ada perubahan' : implode(', ', $parts));

            return redirect()
                ->route('jalur.lowering.import.index')
                ->with('success', $message)
                ->with('import_summary', $summary);
        } catch (\Exception $e) {
            Log::error('Import execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Gagal mengimport data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function sheetSyncPreview(Request $request, GoogleSheetsService $sheetsService)
    {
        $forceUpdate = $request->boolean('force_update');
        $allowRecall = $request->boolean('allow_recall');

        try {
            $sheetData = $sheetsService->getLoweringDataFromSheet();

            $lineNumberMap = JalurLineNumber::pluck('id', 'line_number');

            $existingLowering = JalurLoweringData::select(
                    'id', 'line_number_id', 'status_laporan', 'tanggal_jalur', 'tipe_bongkaran',
                    'penggelaran', 'bongkaran', 'kedalaman_lowering',
                    'cassing_quantity', 'marker_tape_quantity', 'concrete_slab_quantity', 'landasan_quantity'
                )
                ->with(['lineNumber:id,line_number'])
                ->get()
                ->keyBy(fn($item) => ($item->lineNumber->line_number ?? '') . '|' . ($item->tanggal_jalur?->format('Y-m-d') ?? '') . '|' . $item->tipe_bongkaran);

            // Preload existing photo drive_links keyed by lowering_id => [field => drive_link]
            $existingPhotos = PhotoApproval::where('module_name', 'jalur_lowering')
                ->whereIn('photo_field_name', [
                    'foto_evidence_penggelaran_bongkaran',
                    'foto_evidence_cassing',
                    'foto_evidence_marker_tape',
                    'foto_evidence_concrete_slab',
                    'foto_evidence_landasan',
                ])
                ->get()
                ->groupBy('module_record_id')
                ->map(fn($photos) => $photos->pluck('drive_link', 'photo_field_name'));

            $photoFieldMap = [
                'lowering_link'      => 'foto_evidence_penggelaran_bongkaran',
                'cassing_link'       => 'foto_evidence_cassing',
                'marker_tape_link'   => 'foto_evidence_marker_tape',
                'concrete_slab_link' => 'foto_evidence_concrete_slab',
                'landasan_link'      => 'foto_evidence_landasan',
            ];

            $missingLines   = [];
            $detailsNew     = [];
            $detailsUpdate  = [];
            $detailsSkipNC  = [];
            $detailsSkipApp = [];
            $detailsRecall  = [];
            $rowNum         = 0;

            $numericFields = [
                'penggelaran'            => ['label' => 'Penggelaran (m)', 'integer' => false],
                'bongkaran'              => ['label' => 'Bongkaran (m)',   'integer' => false],
                'kedalaman_lowering'     => ['label' => 'Kedalaman (cm)',  'integer' => true],
                'cassing_quantity'       => ['label' => 'Cassing',         'integer' => false],
                'marker_tape_quantity'   => ['label' => 'Marker Tape',     'integer' => false],
                'concrete_slab_quantity' => ['label' => 'Concrete Slab',   'integer' => true],
                'landasan_quantity'      => ['label' => 'Landasan',        'integer' => false],
            ];

            foreach ($sheetData as $row) {
                $rowNum++;
                $lineNumber = $row['line_number'];

                if (!isset($lineNumberMap[$lineNumber])) {
                    if (!isset($missingLines[$lineNumber])) {
                        preg_match('/^\d+-([A-Z0-9]+)-LN/i', $lineNumber, $m);
                        $clusterCode = $m[1] ?? '';
                        $cluster     = $clusterCode ? \App\Models\JalurCluster::where('code_cluster', $clusterCode)->first() : null;
                        $missingLines[$lineNumber] = [
                            'line_number'      => $lineNumber,
                            'diameter'         => $row['diameter'] ?? '',
                            'cluster_code'     => $clusterCode,
                            'cluster_id'       => $cluster?->id,
                            'cluster_name'     => $cluster?->nama_cluster ?? $row['cluster_name'] ?? '',
                            'nama_jalan'       => $row['nama_jalan'] ?? '',
                            'estimasi_panjang' => 0,
                            'status_line'      => 'Aktif',
                        ];
                    }
                    continue;
                }

                $key = $lineNumber . '|' . $row['tanggal_jalur'] . '|' . $row['tipe_bongkaran'];

                if (isset($existingLowering[$key])) {
                    $existing    = $existingLowering[$key];
                    $isApproved  = in_array($existing->status_laporan, ['acc_tracer', 'acc_cgp']);
                    $nonKrusial  = [];

                    foreach ($numericFields as $field => $meta) {
                        $sheetVal = $row[$field] ?? null;
                        if ($sheetVal === null) continue;
                        if ($meta['integer']) {
                            $sn = (int) round((float) $sheetVal);
                            $dn = (int) ($existing->$field ?? 0);
                            if ($sn !== $dn) {
                                $nonKrusial[] = ['field' => $field, 'old' => $dn, 'new' => $sn, 'will_apply' => !$isApproved || $allowRecall];
                            }
                        } else {
                            if ((float) $sheetVal !== (float) ($existing->$field ?? 0)) {
                                $nonKrusial[] = ['field' => $field, 'old' => $existing->$field, 'new' => $sheetVal, 'will_apply' => !$isApproved || $allowRecall];
                            }
                        }
                    }

                    // Photo link comparison
                    $recordPhotos = $existingPhotos[$existing->id] ?? collect();
                    $photoDiff    = [];
                    foreach ($photoFieldMap as $rowField => $photoField) {
                        $sheetLink    = $row[$rowField] ?? null;
                        $existingLink = $recordPhotos[$photoField] ?? null;
                        if ($sheetLink && $sheetLink !== $existingLink) {
                            $photoDiff[] = [
                                'field'      => $photoField,
                                'label'      => ucwords(str_replace(['foto_evidence_', '_'], ['', ' '], $photoField)),
                                'old'        => $existingLink ? 'Ada' : '(kosong)',
                                'new'        => 'Ada (baru)',
                                'will_apply' => !$isApproved || $allowRecall,
                            ];
                        }
                    }

                    $baseDetail = [
                        'row'            => $rowNum,
                        'line_number'    => $lineNumber,
                        'tanggal'        => $row['tanggal_jalur'],
                        'tipe_bongkaran' => $row['tipe_bongkaran'],
                        'status'         => $existing->status_laporan,
                        'existing_id'    => $existing->id,
                        'diff'           => ['non_krusial' => $nonKrusial, 'krusial' => [], 'photos' => $photoDiff],
                        '_row'           => $row,
                    ];

                    $hasChanges = !empty($nonKrusial) || !empty($photoDiff);

                    if (!$hasChanges) {
                        $detailsSkipNC[] = $baseDetail;
                    } elseif ($isApproved && !$allowRecall) {
                        $detailsSkipApp[] = $baseDetail;
                    } elseif ($isApproved && $allowRecall) {
                        $detailsRecall[] = $baseDetail;
                    } else {
                        $detailsUpdate[] = $baseDetail;
                    }
                } else {
                    $detailsNew[] = [
                        'row'            => $rowNum,
                        'line_number'    => $lineNumber,
                        'tanggal'        => $row['tanggal_jalur'],
                        'tipe_bongkaran' => $row['tipe_bongkaran'],
                        'data'           => array_merge($row, [
                            'has_photos'     => !empty($row['lowering_link']) || !empty($row['cassing_link'])
                                             || !empty($row['marker_tape_link']) || !empty($row['concrete_slab_link'])
                                             || !empty($row['landasan_link']),
                            'line_number_id' => $lineNumberMap[$lineNumber],
                        ]),
                        '_row'           => $row,
                    ];
                }
            }

            // Save raw payload to temp JSON for commit
            $tempDir  = storage_path('app/temp-imports');
            if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . time() . '_' . uniqid() . '_sheet_lowering.json';
            file_put_contents($tempFile, json_encode([
                'new'          => array_map(fn($d) => $d['_row'] + ['line_number_id' => $d['data']['line_number_id']], $detailsNew),
                'updated'      => array_map(fn($d) => ['existing_id' => $d['existing_id'], 'data' => $d['_row'], 'is_recall' => false], $detailsUpdate),
                'recalled'     => array_map(fn($d) => ['existing_id' => $d['existing_id'], 'data' => $d['_row'], 'is_recall' => true], $detailsRecall),
                'force_update' => $forceUpdate,
                'allow_recall' => $allowRecall,
            ]));

            $summary = [
                'new'              => count($detailsNew),
                'update'           => count($detailsUpdate) + count($detailsRecall),
                'skip_no_change'   => count($detailsSkipNC),
                'skip_approved'    => count($detailsSkipApp),
                'recall'           => count($detailsRecall),
                'duplicate_in_file'=> 0,
                'error'            => 0,
                'details'          => [
                    'new'              => $detailsNew,
                    'update'           => array_merge($detailsUpdate, $detailsRecall),
                    'skip_no_change'   => $detailsSkipNC,
                    'skip_approved'    => $detailsSkipApp,
                    'recall'           => $detailsRecall,
                    'duplicate_in_file'=> [],
                    'error'            => [],
                ],
            ];

            $clusters = \App\Models\JalurCluster::active()->select('id', 'nama_cluster', 'code_cluster')->get();

            return view('jalur.lowering.import-preview', [
                'summary'      => $summary,
                'fileName'     => 'Google Sheet — ' . config('services.google_sheets.sheet_name_pe', 'PE'),
                'tempFilePath' => 'temp-imports/' . basename($tempFile),
                'forceUpdate'  => $forceUpdate,
                'allowRecall'  => $allowRecall,
                'isSheetSync'  => true,
                'missingLines' => array_values($missingLines),
                'clusters'     => $clusters,
            ]);

        } catch (\Exception $e) {
            Log::error('Lowering sheet sync preview failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengambil data sheet: ' . $e->getMessage());
        }
    }

    private function copyPhotoForLowering(JalurLoweringData $lowering, string $fieldName, string $driveLink, GoogleDriveService $driveService): void
    {
        try {
            // Skip if already downloaded with the same link
            if (PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->where('photo_field_name', $fieldName)
                ->where('drive_link', $driveLink)
                ->exists()) return;

            // Delete old approval for this field before replacing
            PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->where('photo_field_name', $fieldName)
                ->delete();

            $lineNumberStr = $lowering->lineNumber->line_number ?? 'unknown';
            $clusterName   = $lowering->lineNumber->cluster->nama_cluster ?? 'unknown';
            $clusterSlug   = Str::slug($clusterName, '_');
            $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
            $customPath    = "jalur_lowering/{$clusterSlug}/{$lineNumberStr}/{$tanggalFolder}";
            $fileName      = "{$fieldName}_" . now()->format('YmdHis');

            $result = $driveService->copyFromDriveLink($driveLink, $customPath, $fileName);

            PhotoApproval::create([
                'module_name'      => 'jalur_lowering',
                'module_record_id' => $lowering->id,
                'photo_field_name' => $fieldName,
                'photo_url'        => $result['url'],
                'drive_file_id'    => $result['id'] ?? null,
                'drive_link'       => $driveLink,
                'photo_status'     => 'tracer_pending',
                'uploaded_by'      => Auth::id(),
                'uploaded_at'      => now(),
            ]);

            Log::info('Lowering photo copied from Drive', ['lowering_id' => $lowering->id, 'field' => $fieldName]);
        } catch (\Exception $e) {
            Log::error('Failed to copy lowering photo from Drive', [
                'lowering_id' => $lowering->id,
                'field'       => $fieldName,
                'drive_link'  => $driveLink,
                'error'       => $e->getMessage(),
            ]);
            // Jangan throw — foto gagal tidak boleh rollback data record
        }
    }

    public function sheetSyncCommit(Request $request, GoogleDriveService $driveService)
    {
        $tempFilePath = storage_path('app/' . $request->input('temp_file_path'));

        if (!file_exists($tempFilePath)) {
            return redirect()->route('jalur.lowering.import.index')
                ->with('error', 'File temporary tidak ditemukan. Silakan fetch ulang dari sheet.');
        }

        $payload     = json_decode(file_get_contents($tempFilePath), true);
        $forceUpdate = $payload['force_update'] ?? false;
        $allowRecall = $payload['allow_recall'] ?? false;

        try {
            DB::beginTransaction();
            $lineNumberMap = JalurLineNumber::pluck('id', 'line_number');
            $toInt = fn($v, $fb) => isset($v) ? (int) round((float) $v) : $fb;
            $countNew = $countUpdated = $countRecalled = 0;

            foreach ($payload['new'] ?? [] as $item) {
                $lineNumberId = $item['line_number_id'] ?? ($lineNumberMap[$item['line_number']] ?? null);
                if (!$lineNumberId) continue;
                $lowering = JalurLoweringData::create([
                    'line_number_id'         => $lineNumberId,
                    'tanggal_jalur'          => $item['tanggal_jalur'],
                    'tipe_bongkaran'         => $item['tipe_bongkaran'],
                    'penggelaran'            => $item['penggelaran'],
                    'bongkaran'              => $item['bongkaran'],
                    'kedalaman_lowering'     => $toInt($item['kedalaman_lowering'] ?? null, null),
                    'cassing_quantity'       => $item['cassing_quantity'] ?? null,
                    'marker_tape_quantity'   => $item['marker_tape_quantity'] ?? null,
                    'concrete_slab_quantity' => $toInt($item['concrete_slab_quantity'] ?? null, null),
                    'landasan_quantity'      => $item['landasan_quantity'] ?? null,
                    'aksesoris_cassing'      => !empty($item['cassing_quantity']),
                    'aksesoris_marker_tape'  => !empty($item['marker_tape_quantity']),
                    'aksesoris_concrete_slab'=> !empty($item['concrete_slab_quantity']),
                    'aksesoris_landasan'     => !empty($item['landasan_quantity']),
                    'status_laporan'         => 'draft',
                    'created_by'             => Auth::id(),
                    'updated_by'             => Auth::id(),
                ]);
                $lowering->load('lineNumber.cluster');
                $photoMap = [
                    'foto_evidence_penggelaran_bongkaran' => $item['lowering_link']      ?? null,
                    'foto_evidence_cassing'               => $item['cassing_link']       ?? null,
                    'foto_evidence_marker_tape'           => $item['marker_tape_link']   ?? null,
                    'foto_evidence_concrete_slab'         => $item['concrete_slab_link'] ?? null,
                    'foto_evidence_landasan'              => $item['landasan_link']      ?? null,
                ];
                foreach ($photoMap as $field => $link) {
                    if (!empty($link)) {
                        $this->copyPhotoForLowering($lowering, $field, $link, $driveService);
                    }
                }
                $countNew++;
            }

            foreach (array_merge($payload['updated'] ?? [], $payload['recalled'] ?? []) as $item) {
                $existing = JalurLoweringData::find($item['existing_id']);
                if (!$existing) continue;

                $d          = $item['data'];
                $isRecall   = $item['is_recall'] ?? false;
                $updateData = [
                    'penggelaran'            => $d['penggelaran']            ?? $existing->penggelaran,
                    'bongkaran'              => $d['bongkaran']              ?? $existing->bongkaran,
                    'kedalaman_lowering'     => $toInt($d['kedalaman_lowering'] ?? null, $existing->kedalaman_lowering),
                    'cassing_quantity'       => $d['cassing_quantity']       ?? $existing->cassing_quantity,
                    'marker_tape_quantity'   => $d['marker_tape_quantity']   ?? $existing->marker_tape_quantity,
                    'concrete_slab_quantity' => $toInt($d['concrete_slab_quantity'] ?? null, $existing->concrete_slab_quantity),
                    'landasan_quantity'      => $d['landasan_quantity']      ?? $existing->landasan_quantity,
                    'updated_by'             => Auth::id(),
                ];

                if ($isRecall) {
                    $updateData['status_laporan']     = 'draft';
                    $updateData['tracer_approved_at'] = null;
                    $updateData['tracer_approved_by'] = null;
                    $updateData['cgp_approved_at']    = null;
                    $updateData['cgp_approved_by']    = null;
                    $updateData['tracer_notes']       = null;
                    $updateData['cgp_notes']          = null;
                    $existing->photoApprovals()->update([
                        'photo_status'       => 'tracer_pending',
                        'tracer_approved_at' => null, 'tracer_approved_by' => null, 'tracer_notes' => null,
                        'cgp_approved_at'    => null, 'cgp_approved_by'    => null, 'cgp_notes'    => null,
                    ]);
                    $countRecalled++;
                }

                $existing->update($updateData);

                // Download foto baru jika link berubah
                $existing->load('lineNumber.cluster');
                $photoMap = [
                    'foto_evidence_penggelaran_bongkaran' => $d['lowering_link']      ?? null,
                    'foto_evidence_cassing'               => $d['cassing_link']       ?? null,
                    'foto_evidence_marker_tape'           => $d['marker_tape_link']   ?? null,
                    'foto_evidence_concrete_slab'         => $d['concrete_slab_link'] ?? null,
                    'foto_evidence_landasan'              => $d['landasan_link']      ?? null,
                ];
                foreach ($photoMap as $field => $link) {
                    if (!empty($link)) {
                        $this->copyPhotoForLowering($existing, $field, $link, $driveService);
                    }
                }

                $countUpdated++;
            }

            DB::commit();
            @unlink($tempFilePath);

            $parts = [];
            if ($countNew > 0)      $parts[] = "{$countNew} data baru";
            if ($countUpdated > 0)  $parts[] = "{$countUpdated} data diupdate";
            if ($countRecalled > 0) $parts[] = "{$countRecalled} data di-recall (→ draft)";

            return redirect()->route('jalur.lowering.import.index')
                ->with('success', 'Sync selesai: ' . (empty($parts) ? 'tidak ada perubahan' : implode(', ', $parts)));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lowering sheet sync commit failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal commit: ' . $e->getMessage());
        }
    }

    public function sheetCreateLines(Request $request)
    {
        $request->validate([
            'lines'                    => 'required|array|min:1',
            'lines.*.line_number'      => 'required|string',
            'lines.*.cluster_id'       => 'required|integer|exists:jalur_clusters,id',
            'lines.*.diameter'         => 'required|string',
            'lines.*.nama_jalan'       => 'nullable|string|max:255',
            'lines.*.estimasi_panjang' => 'nullable|numeric|min:0',
            'lines.*.status_line'      => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();
            $created = 0;
            $skipped = 0;

            foreach ($request->input('lines') as $line) {
                if (JalurLineNumber::where('line_number', $line['line_number'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Derive line_code from line_number e.g. '63-KWR-LN040B' → 'LN040B'
                preg_match('/(LN\w+)$/i', $line['line_number'], $m);
                $lineCode = $m[1] ?? $line['line_number'];

                JalurLineNumber::create([
                    'cluster_id'        => $line['cluster_id'],
                    'line_number'       => $line['line_number'],
                    'line_code'         => $lineCode,
                    'diameter'          => $line['diameter'],
                    'nama_jalan'        => $line['nama_jalan'] ?? null,
                    'estimasi_panjang'  => $line['estimasi_panjang'] ?? 0,
                    'total_penggelaran' => 0,
                    'is_active'         => true,
                    'created_by'        => Auth::id(),
                    'updated_by'        => Auth::id(),
                ]);
                $created++;
            }

            DB::commit();

            $parts = [];
            if ($created > 0) $parts[] = "{$created} line number berhasil dibuat";
            if ($skipped > 0) $parts[] = "{$skipped} sudah ada";

            return response()->json([
                'success' => true,
                'message' => implode(', ', $parts),
                'created' => $created,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('sheetCreateLines failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sheetSyncProcess(Request $request)
    {
        try {
            DB::beginTransaction();

            $lineNumberMap = JalurLineNumber::pluck('id', 'line_number');
            $forceUpdate   = $request->boolean('force_update');
            $allowRecall   = $request->boolean('allow_recall');
            $countNew      = 0;
            $countUpdated  = 0;
            $countSkipped  = 0;
            $countRecalled = 0;

            foreach ($request->input('sync_new', []) as $item) {
                $item = is_string($item) ? json_decode($item, true) : $item;
                if (!$item) continue;

                $lineNumberId = $lineNumberMap[$item['line_number']] ?? null;
                if (!$lineNumberId) continue;

                JalurLoweringData::create([
                    'line_number_id'         => $lineNumberId,
                    'tanggal_jalur'          => $item['tanggal_jalur'],
                    'tipe_bongkaran'         => $item['tipe_bongkaran'],
                    'penggelaran'            => $item['penggelaran'],
                    'bongkaran'              => $item['bongkaran'],
                    'kedalaman_lowering'     => isset($item['kedalaman_lowering']) ? (int) round((float) $item['kedalaman_lowering']) : null,
                    'cassing_quantity'       => $item['cassing_quantity'] ?? null,
                    'marker_tape_quantity'   => $item['marker_tape_quantity'] ?? null,
                    'concrete_slab_quantity' => isset($item['concrete_slab_quantity']) ? (int) round((float) $item['concrete_slab_quantity']) : null,
                    'landasan_quantity'      => $item['landasan_quantity'] ?? null,
                    'aksesoris_cassing'      => !empty($item['cassing_quantity']),
                    'aksesoris_marker_tape'  => !empty($item['marker_tape_quantity']),
                    'aksesoris_concrete_slab'=> !empty($item['concrete_slab_quantity']),
                    'aksesoris_landasan'     => !empty($item['landasan_quantity']),
                    'status_laporan'         => 'draft',
                    'created_by'             => Auth::id(),
                    'updated_by'             => Auth::id(),
                ]);
                $countNew++;
            }

            foreach ($request->input('sync_updated', []) as $item) {
                $item = is_string($item) ? json_decode($item, true) : $item;
                if (!$item || !isset($item['existing_id'])) continue;

                $existing = JalurLoweringData::find($item['existing_id']);
                if (!$existing) continue;

                $isApproved = in_array($existing->status_laporan, ['acc_tracer', 'acc_cgp']);

                if ($isApproved && !$allowRecall) {
                    $countSkipped++;
                    continue;
                }

                $d     = $item['data'];
                $toInt = fn($v, $fallback) => isset($v) ? (int) round((float) $v) : $fallback;

                $updateData = $forceUpdate ? [
                    // force update: overwrite all fields from sheet
                    'penggelaran'            => $d['penggelaran']            ?? $existing->penggelaran,
                    'bongkaran'              => $d['bongkaran']              ?? $existing->bongkaran,
                    'kedalaman_lowering'     => $toInt($d['kedalaman_lowering']     ?? null, $existing->kedalaman_lowering),
                    'cassing_quantity'       => $d['cassing_quantity']       ?? $existing->cassing_quantity,
                    'marker_tape_quantity'   => $d['marker_tape_quantity']   ?? $existing->marker_tape_quantity,
                    'concrete_slab_quantity' => $toInt($d['concrete_slab_quantity'] ?? null, $existing->concrete_slab_quantity),
                    'landasan_quantity'      => $d['landasan_quantity']      ?? $existing->landasan_quantity,
                    'updated_by'             => Auth::id(),
                ] : [
                    // default: only fill empty fields
                    'penggelaran'            => $existing->penggelaran            ?? $d['penggelaran'],
                    'bongkaran'              => $existing->bongkaran              ?? $d['bongkaran'],
                    'kedalaman_lowering'     => $existing->kedalaman_lowering     ?? $toInt($d['kedalaman_lowering'] ?? null, null),
                    'cassing_quantity'       => $existing->cassing_quantity       ?? $d['cassing_quantity'],
                    'marker_tape_quantity'   => $existing->marker_tape_quantity   ?? $d['marker_tape_quantity'],
                    'concrete_slab_quantity' => $existing->concrete_slab_quantity ?? $toInt($d['concrete_slab_quantity'] ?? null, null),
                    'landasan_quantity'      => $existing->landasan_quantity      ?? $d['landasan_quantity'],
                    'updated_by'             => Auth::id(),
                ];

                if ($isApproved && $allowRecall) {
                    $updateData['status_laporan']     = 'draft';
                    $updateData['tracer_approved_at'] = null;
                    $updateData['tracer_approved_by'] = null;
                    $updateData['cgp_approved_at']    = null;
                    $updateData['cgp_approved_by']    = null;
                    $updateData['tracer_notes']       = null;
                    $updateData['cgp_notes']          = null;

                    $existing->photoApprovals()->update([
                        'photo_status'       => 'tracer_pending',
                        'tracer_approved_at' => null,
                        'tracer_approved_by' => null,
                        'tracer_notes'       => null,
                        'cgp_approved_at'    => null,
                        'cgp_approved_by'    => null,
                        'cgp_notes'          => null,
                    ]);
                    $countRecalled++;
                }

                $existing->update($updateData);
                $countUpdated++;
            }

            DB::commit();

            $parts = [];
            if ($countNew > 0)      $parts[] = "{$countNew} data baru";
            if ($countUpdated > 0)  $parts[] = "{$countUpdated} data diupdate";
            if ($countRecalled > 0) $parts[] = "{$countRecalled} data di-recall (→ draft)";
            if ($countSkipped > 0)  $parts[] = "{$countSkipped} data dilindungi";

            return response()->json([
                'success'       => true,
                'message'       => 'Sync selesai: ' . (empty($parts) ? 'tidak ada perubahan' : implode(', ', $parts)),
                'count_new'     => $countNew,
                'count_updated' => $countUpdated,
                'count_skipped' => $countSkipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lowering sheet sync process failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function duplicates()
    {
        $duplicateKeys = JalurLoweringData::select(
                'line_number_id', 'tanggal_jalur', 'tipe_bongkaran',
                'penggelaran', 'bongkaran', 'kedalaman_lowering'
            )
            ->selectRaw('COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupBy('line_number_id', 'tanggal_jalur', 'tipe_bongkaran', 'penggelaran', 'bongkaran', 'kedalaman_lowering')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('line_number_id')
            ->orderBy('tanggal_jalur')
            ->get();

        $groups = [];
        foreach ($duplicateKeys as $key) {
            $query = JalurLoweringData::with(['lineNumber.cluster', 'createdBy', 'updatedBy'])
                ->where('line_number_id', $key->line_number_id)
                ->where('tanggal_jalur', $key->tanggal_jalur)
                ->where('tipe_bongkaran', $key->tipe_bongkaran)
                ->where('penggelaran', $key->penggelaran)
                ->where('bongkaran', $key->bongkaran);

            if ($key->kedalaman_lowering === null) {
                $query->whereNull('kedalaman_lowering');
            } else {
                $query->where('kedalaman_lowering', $key->kedalaman_lowering);
            }

            $records = $query->orderBy('id')->get();

            $recordIds = $records->pluck('id')->all();
            $photoCounts = PhotoApproval::where('module_name', 'jalur_lowering')
                ->whereIn('module_record_id', $recordIds)
                ->select('module_record_id', DB::raw('COUNT(*) as total'))
                ->groupBy('module_record_id')
                ->pluck('total', 'module_record_id');

            $groups[] = [
                'key' => $key->line_number_id . '|' . $key->tanggal_jalur . '|' . $key->tipe_bongkaran,
                'line_number' => $records->first()->lineNumber->line_number ?? '-',
                'cluster' => $records->first()->lineNumber->cluster->nama_cluster ?? '-',
                'tanggal_jalur' => $key->tanggal_jalur,
                'tipe_bongkaran' => $key->tipe_bongkaran,
                'total' => $key->total,
                'records' => $records->map(function ($r) use ($photoCounts) {
                    return [
                        'id' => $r->id,
                        'status_laporan' => $r->status_laporan,
                        'nama_jalan' => $r->nama_jalan,
                        'penggelaran' => $r->penggelaran,
                        'bongkaran' => $r->bongkaran,
                        'kedalaman_lowering' => $r->kedalaman_lowering,
                        'cassing_quantity' => $r->cassing_quantity,
                        'marker_tape_quantity' => $r->marker_tape_quantity,
                        'concrete_slab_quantity' => $r->concrete_slab_quantity,
                        'landasan_quantity' => $r->landasan_quantity,
                        'keterangan' => $r->keterangan,
                        'photo_count' => $photoCounts->get($r->id, 0),
                        'created_by' => $r->createdBy->name ?? '-',
                        'updated_by' => $r->updatedBy->name ?? '-',
                        'created_at' => $r->created_at?->format('Y-m-d H:i'),
                        'updated_at' => $r->updated_at?->format('Y-m-d H:i'),
                        'is_approved' => in_array($r->status_laporan, ['acc_tracer', 'acc_cgp']),
                    ];
                })->all(),
            ];
        }

        return view('jalur.lowering.duplicates', [
            'groups' => $groups,
            'totalGroups' => count($groups),
            'totalRecordsToDelete' => array_sum(array_map(fn($g) => $g['total'] - 1, $groups)),
        ]);
    }

    public function resolveDuplicates(Request $request)
    {
        $request->validate([
            'keep' => 'required|array',
            'keep.*' => 'required|integer|exists:jalur_lowering_data,id',
        ]);

        $keepIds = array_values(array_map('intval', $request->input('keep')));

        DB::beginTransaction();
        try {
            $totalDeleted = 0;

            foreach ($keepIds as $keepId) {
                $keepRecord = JalurLoweringData::findOrFail($keepId);

                $deleteQuery = JalurLoweringData::where('line_number_id', $keepRecord->line_number_id)
                    ->where('tanggal_jalur', $keepRecord->tanggal_jalur)
                    ->where('tipe_bongkaran', $keepRecord->tipe_bongkaran)
                    ->where('penggelaran', $keepRecord->penggelaran)
                    ->where('bongkaran', $keepRecord->bongkaran)
                    ->where('id', '!=', $keepId);

                if ($keepRecord->kedalaman_lowering === null) {
                    $deleteQuery->whereNull('kedalaman_lowering');
                } else {
                    $deleteQuery->where('kedalaman_lowering', $keepRecord->kedalaman_lowering);
                }

                $toDelete = $deleteQuery->get();

                foreach ($toDelete as $record) {
                    Log::info('Lowering duplicate soft-deleted', [
                        'deleted_id' => $record->id,
                        'kept_id' => $keepId,
                        'line_number_id' => $record->line_number_id,
                        'tanggal' => $record->tanggal_jalur,
                        'tipe' => $record->tipe_bongkaran,
                        'resolved_by' => Auth::id(),
                    ]);
                    $record->delete();
                    $totalDeleted++;
                }
            }

            DB::commit();

            $keptCount = count($keepIds);
            return redirect()
                ->route('jalur.lowering.duplicates')
                ->with('success', "Resolusi selesai: {$totalDeleted} record duplikat dihapus (soft-delete), {$keptCount} record dipertahankan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resolve lowering duplicates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal menghapus duplikat: ' . $e->getMessage());
        }
    }
}
