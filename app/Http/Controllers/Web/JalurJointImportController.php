<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurJointImport;
use App\Exports\JalurJointTemplateExport;
use App\Models\JalurJointData;
use App\Models\JalurLineNumber;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class JalurJointImportController extends Controller
{
    public function index()
    {
        return view('jalur.joint.import-index');
    }

    public function downloadTemplate()
    {
        $filename = 'Template_Import_Joint_Data_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new JalurJointTemplateExport(), $filename);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $forceUpdate = $request->boolean('force_update');
        $allowRecall = $request->boolean('allow_recall');

        try {
            $file    = $request->file('file');
            $tempDir = storage_path('app/temp-imports');

            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $destination  = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;
            $saved        = copy($file->getRealPath(), $destination);

            if (!$saved || !file_exists($destination)) {
                throw new \Exception('Gagal menyimpan file temporary. Silakan coba lagi.');
            }

            $import = new JalurJointImport(true, $destination, $forceUpdate, $allowRecall);
            Excel::import($import, $destination);

            $summary = $import->getSummary();

            Log::info('Joint import preview completed', [
                'new'            => $summary['new'],
                'update'         => $summary['update'],
                'skip_no_change' => $summary['skip_no_change'],
                'skip_approved'  => $summary['skip_approved'],
                'recall'         => $summary['recall'],
                'error'          => $summary['error'],
                'duplicate'      => $summary['duplicate_in_file'],
                'force_update'   => $forceUpdate,
                'allow_recall'   => $allowRecall,
            ]);

            return view('jalur.joint.import-preview', [
                'summary'      => $summary,
                'fileName'     => $file->getClientOriginalName(),
                'tempFilePath' => 'temp-imports/' . $tempFileName,
                'forceUpdate'  => $forceUpdate,
                'allowRecall'  => $allowRecall,
            ]);

        } catch (\Exception $e) {
            Log::error('Joint import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'file' => 'Gagal memproses file: ' . $e->getMessage(),
            ]);
        }
    }

    public function import(Request $request)
    {
        Log::info('Joint import execute started', [
            'has_temp_file'  => $request->has('temp_file_path'),
            'has_file_upload'=> $request->hasFile('file'),
        ]);

        $forceUpdate = $request->boolean('force_update');
        $allowRecall = $request->boolean('allow_recall');

        // Resolve file: from temp (preview flow) or direct upload
        if ($request->has('temp_file_path')) {
            $relativePath = $request->input('temp_file_path');
            $tempFilePath = storage_path('app') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (!file_exists($tempFilePath)) {
                Log::error('Joint import: temp file not found', ['path' => $tempFilePath]);
                return redirect()->route('jalur.joint.import.index')
                    ->with('error', 'File temporary tidak ditemukan. Silakan upload ulang.');
            }

            $file = $tempFilePath;
        } else {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
            ]);
            $file = $request->file('file');
        }

        try {
            set_time_limit(0);
            ini_set('memory_limit', '-1');
            DB::connection()->disableQueryLog();

            $import = new JalurJointImport(
                false,
                is_string($file) ? $file : $file->getRealPath(),
                $forceUpdate,
                $allowRecall
            );
            Excel::import($import, $file);

            $summary = $import->getSummary();

            Log::info('Joint import completed', [
                'new'          => $summary['new'],
                'update'       => $summary['update'],
                'recall'       => $summary['recall'],
                'skip'         => $summary['skip_no_change'] + $summary['skip_approved'],
                'error'        => $summary['error'],
                'force_update' => $forceUpdate,
                'allow_recall' => $allowRecall,
            ]);

            // Sync joint counts after successful import
            $imported = $summary['new'] + $summary['update'] + $summary['recall'];
            if ($imported > 0) {
                try {
                    \Artisan::call('jalur:sync-joint-count', ['--force' => true]);
                    Log::info('Joint counts synced after import');
                } catch (\Exception $e) {
                    Log::warning('Failed to sync joint counts', ['error' => $e->getMessage()]);
                }
            }

            // Clean up temp file
            if ($request->has('temp_file_path') && isset($tempFilePath) && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            // Build result message
            $parts = [];
            if ($summary['new'] > 0)    $parts[] = "{$summary['new']} baru";
            if ($summary['update'] > 0) $parts[] = "{$summary['update']} diupdate";
            if ($summary['recall'] > 0) $parts[] = "{$summary['recall']} di-recall";

            if ($summary['error'] > 0) {
                $skipped = $summary['skip_no_change'] + $summary['skip_approved'] + $summary['error'] + $summary['duplicate_in_file'];
                return redirect()->route('jalur.joint.import.index')
                    ->with('warning', 'Import selesai: ' . implode(', ', $parts) . ". {$summary['error']} baris gagal dilewati.")
                    ->with('import_summary', $summary);
            }

            return redirect()->route('jalur.joint.index')
                ->with('success', 'Import berhasil: ' . implode(', ', $parts) . '.')
                ->with('import_summary', $summary);

        } catch (\Exception $e) {
            Log::error('Joint import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'file' => 'Import gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function sheetSyncPreview(Request $request, GoogleSheetsService $sheetsService)
    {
        $forceUpdate = $request->boolean('force_update');
        $allowRecall = $request->boolean('allow_recall');

        try {
            $sheetData = $sheetsService->getJointDataFromSheet();

            $existingJoints = JalurJointData::select(
                    'id', 'nomor_joint', 'fitting_type_id', 'status_laporan',
                    'tanggal_joint', 'joint_line_from', 'joint_line_to', 'tipe_penyambungan', 'diameter'
                )
                ->with(['fittingType:id,nama_fitting'])
                ->orderBy('id')
                ->get()
                ->keyBy('nomor_joint'); // duplicate nomor_joint: keeps highest id (last in asc order)

            $fittingMap  = JalurFittingType::all()->mapWithKeys(fn($f) => [strtoupper($f->nama_fitting) => $f->id]);
            $clusterMap  = JalurCluster::pluck('nama_cluster', 'id');
            $fittingNMap = JalurFittingType::pluck('nama_fitting', 'id');

            $textFields = [
                'tanggal_joint'     => 'Tanggal',
                'joint_line_from'   => 'Line From',
                'joint_line_to'     => 'Line To',
                'tipe_penyambungan' => 'Tipe Penyambungan',
                'diameter'          => 'Diameter',
            ];

            $details      = [];
            $rowNum       = 0;
            $seenInSheet  = []; // track nomor_joint sudah diproses dalam sheet ini
            $counts       = ['new' => 0, 'update' => 0, 'recall' => 0, 'skip_no_change' => 0, 'skip_approved' => 0, 'error' => 0, 'duplicate_in_file' => 0];

            foreach ($sheetData as $row) {
                $rowNum++;
                $nomorJoint = $row['nomor_joint'];

                // Skip duplikat dalam sheet — nomor_joint yang sama hanya diproses sekali
                if (isset($seenInSheet[$nomorJoint])) {
                    $details[] = [
                        'row'     => $rowNum,
                        'status'  => 'duplicate_in_file',
                        'data'    => ['joint_number' => $nomorJoint],
                        'message' => "Duplikat dalam sheet (sudah diproses di baris {$seenInSheet[$nomorJoint]})",
                    ];
                    $counts['duplicate_in_file']++;
                    continue;
                }
                $seenInSheet[$nomorJoint] = $rowNum;

                if (isset($existingJoints[$nomorJoint])) {
                    $existing   = $existingJoints[$nomorJoint];
                    $isApproved = in_array($existing->status_laporan, ['acc_tracer', 'acc_cgp']);
                    $diff       = [];

                    foreach ($textFields as $field => $label) {
                        $sheetVal = trim($row[$field] ?? '');
                        $rawDb    = $existing->$field ?? '';
                        $dbVal    = ($rawDb instanceof \Carbon\Carbon) ? $rawDb->format('Y-m-d') : trim((string) $rawDb);
                        if ($sheetVal !== '' && strtolower($sheetVal) !== strtolower($dbVal)) {
                            $diff[] = ['label' => $label, 'old' => $dbVal ?: '(kosong)', 'new' => $sheetVal, 'will_apply' => !$isApproved || $allowRecall, 'krusial' => false];
                        }
                    }

                    $sheetFitting   = strtoupper($row['fitting_name'] ?? '');
                    $sheetFittingId = $fittingMap[$sheetFitting] ?? null;
                    if ($sheetFitting && $sheetFittingId && $sheetFittingId !== $existing->fitting_type_id) {
                        $diff[] = [
                            'label'      => 'Fitting Type',
                            'old'        => $existing->fittingType?->nama_fitting ?? '(kosong)',
                            'new'        => $row['fitting_name'],
                            'will_apply' => !$isApproved || $allowRecall,
                            'krusial'    => false,
                        ];
                    }

                    $baseItem = [
                        'row'          => $rowNum,
                        'data'         => ['joint_number' => $nomorJoint, 'existing_status' => $existing->status_laporan, 'foto_hyperlink' => ''],
                        'diff'         => $diff,
                        'photo_changed'=> false,
                        'existing_id'  => $existing->id,
                        '_payload'     => $row,
                    ];

                    if (empty($diff)) {
                        $details[] = array_merge($baseItem, ['status' => 'skip_no_change', 'message' => 'Tidak ada perubahan']);
                        $counts['skip_no_change']++;
                    } elseif ($isApproved && !$allowRecall) {
                        $details[] = array_merge($baseItem, ['status' => 'skip_approved']);
                        $counts['skip_approved']++;
                    } elseif ($isApproved && $allowRecall) {
                        $details[] = array_merge($baseItem, ['status' => 'recall']);
                        $counts['recall']++;
                    } else {
                        $details[] = array_merge($baseItem, ['status' => 'update']);
                        $counts['update']++;
                    }
                } else {
                    $lineFrom  = $row['joint_line_from'] ?? '';
                    $clusterId = JalurLineNumber::where('line_number', $lineFrom)->value('cluster_id');

                    if (!$clusterId) {
                        preg_match('/^\d+-([A-Z0-9]+)-LN/i', $lineFrom, $m);
                        $clusterCode = $m[1] ?? null;
                        if (!$clusterCode) {
                            preg_match('/^([A-Z]{2,5})-/i', $nomorJoint, $m2);
                            $clusterCode = $m2[1] ?? null;
                        }
                        if ($clusterCode) {
                            $clusterId = JalurCluster::where('code_cluster', strtoupper($clusterCode))->value('id');
                        }
                    }

                    if (!$clusterId) {
                        $details[] = [
                            'row'    => $rowNum,
                            'status' => 'error',
                            'data'   => ['joint_number' => $nomorJoint],
                            'message'=> "Cluster tidak ditemukan (line_from: '{$lineFrom}')",
                        ];
                        $counts['error']++;
                        continue;
                    }

                    $jointCode = $this->parseJointCode($nomorJoint);
                    if ($jointCode === null) {
                        $details[] = [
                            'row'     => $rowNum,
                            'status'  => 'error',
                            'data'    => ['joint_number' => $nomorJoint],
                            'message' => "Format nomor joint tidak valid: '{$nomorJoint}'. Gunakan format {CLUSTER}-{FITTING}{KODE} (mis. SPD-ECP033) atau {TIPE}.{KODE} untuk diameter 180 (mis. BF.46).",
                        ];
                        $counts['error']++;
                        continue;
                    }

                    $fittingId = $fittingMap[strtoupper($row['fitting_name'] ?? '')] ?? null;
                    $details[] = [
                        'row'    => $rowNum,
                        'status' => 'new',
                        'data'   => [
                            'joint_number'     => $nomorJoint,
                            'cluster'          => $clusterMap[$clusterId] ?? '',
                            'fitting_type'     => $fittingNMap[$fittingId] ?? ($row['fitting_name'] ?? '-'),
                            'tanggal_joint'    => $row['tanggal_joint'],
                            'joint_line_from'  => $row['joint_line_from'] ?? '',
                            'joint_line_to'    => $row['joint_line_to'] ?? '',
                            'tipe_penyambungan'=> $row['tipe_penyambungan'] ?? '',
                            'foto_hyperlink'   => '',
                        ],
                        '_payload' => array_merge($row, [
                            'cluster_id'      => $clusterId,
                            'fitting_type_id' => $fittingId,
                            'joint_code'      => $jointCode,
                        ]),
                    ];
                    $counts['new']++;
                }
            }

            // Save payload to temp JSON
            $tempDir  = storage_path('app/temp-imports');
            if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . time() . '_' . uniqid() . '_sheet_joint.json';
            file_put_contents($tempFile, json_encode([
                'details'      => array_map(fn($d) => ['status' => $d['status'], 'existing_id' => $d['existing_id'] ?? null, 'payload' => $d['_payload'] ?? null], $details),
                'force_update' => $forceUpdate,
                'allow_recall' => $allowRecall,
            ]));

            $summary = [
                'new'               => $counts['new'],
                'update'            => $counts['update'],
                'recall'            => $counts['recall'],
                'skip_no_change'    => $counts['skip_no_change'],
                'skip_approved'     => $counts['skip_approved'],
                'error'             => $counts['error'],
                'duplicate_in_file' => $counts['duplicate_in_file'],
                'db_duplicate'      => 0,
                'details'           => $details,
            ];

            return view('jalur.joint.import-preview', [
                'summary'      => $summary,
                'fileName'     => 'Google Sheet — ' . config('services.google_sheets.sheet_name_pe', 'PE'),
                'tempFilePath' => 'temp-imports/' . basename($tempFile),
                'forceUpdate'  => $forceUpdate,
                'allowRecall'  => $allowRecall,
                'isSheetSync'  => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Joint sheet sync preview failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengambil data sheet: ' . $e->getMessage());
        }
    }

    public function sheetSyncCommit(Request $request)
    {
        $tempFilePath = storage_path('app/' . $request->input('temp_file_path'));

        if (!file_exists($tempFilePath)) {
            return redirect()->route('jalur.joint.import.index')
                ->with('error', 'File temporary tidak ditemukan. Silakan fetch ulang dari sheet.');
        }

        $payload     = json_decode(file_get_contents($tempFilePath), true);
        $forceUpdate = $payload['force_update'] ?? false;
        $allowRecall = $payload['allow_recall'] ?? false;

        try {
            DB::beginTransaction();

            $fittingMap    = JalurFittingType::all()->mapWithKeys(fn($f) => [strtoupper($f->nama_fitting) => $f->id]);
            $countNew      = $countUpdated = $countRecalled = 0;

            foreach ($payload['details'] ?? [] as $item) {
                $status  = $item['status'];
                $p       = $item['payload'];

                if ($status === 'new') {
                    if (empty($p['cluster_id'])) continue;
                    if (empty($p['tipe_penyambungan'])) continue; // NOT NULL, skip jika kosong/invalid
                    // Safety: skip jika nomor_joint sudah ada di DB (duplikat lolos dari preview)
                    if (JalurJointData::where('nomor_joint', $p['nomor_joint'])->exists()) continue;
                    JalurJointData::create([
                        'cluster_id'        => $p['cluster_id'],
                        'fitting_type_id'   => $p['fitting_type_id'] ?? null,
                        'nomor_joint'       => $p['nomor_joint'],
                        'joint_code'        => $p['joint_code'] ?? $p['nomor_joint'],
                        'tanggal_joint'     => $p['tanggal_joint'],
                        'joint_line_from'   => $p['joint_line_from'] ?? '',
                        'joint_line_to'     => $p['joint_line_to'] ?? '',
                        'tipe_penyambungan' => $p['tipe_penyambungan'],
                        'diameter'          => $p['diameter'] ?? null,
                        'status_laporan'    => 'draft',
                        'created_by'        => Auth::id(),
                        'updated_by'        => Auth::id(),
                    ]);
                    $countNew++;

                } elseif (in_array($status, ['update', 'recall'])) {
                    $existing = JalurJointData::find($item['existing_id']);
                    if (!$existing) continue;

                    $fittingId = isset($p['fitting_name']) ? ($fittingMap[strtoupper($p['fitting_name'])] ?? $existing->fitting_type_id) : $existing->fitting_type_id;

                    $updateData = [
                        'tanggal_joint'     => $p['tanggal_joint']     ?? $existing->tanggal_joint,
                        'joint_line_from'   => $p['joint_line_from']   ?? $existing->joint_line_from,
                        'joint_line_to'     => $p['joint_line_to']     ?? $existing->joint_line_to,
                        'tipe_penyambungan' => $p['tipe_penyambungan'] ?? $existing->tipe_penyambungan,
                        'diameter'          => $p['diameter']          ?? $existing->diameter,
                        'fitting_type_id'   => $fittingId,
                        'updated_by'        => Auth::id(),
                    ];

                    if ($status === 'recall') {
                        $updateData['status_laporan']     = 'draft';
                        $updateData['tracer_approved_at'] = null;
                        $updateData['tracer_approved_by'] = null;
                        $updateData['cgp_approved_at']    = null;
                        $updateData['cgp_approved_by']    = null;
                        $existing->photoApprovals()->update([
                            'photo_status'       => 'tracer_pending',
                            'tracer_approved_at' => null, 'tracer_approved_by' => null, 'tracer_notes' => null,
                            'cgp_approved_at'    => null, 'cgp_approved_by'    => null, 'cgp_notes'    => null,
                        ]);
                        $countRecalled++;
                    }

                    $existing->update($updateData);
                    $countUpdated++;
                }
            }

            DB::commit();
            @unlink($tempFilePath);

            $parts = [];
            if ($countNew > 0)      $parts[] = "{$countNew} data baru";
            if ($countUpdated > 0)  $parts[] = "{$countUpdated} data diupdate";
            if ($countRecalled > 0) $parts[] = "{$countRecalled} data di-recall (→ draft)";

            return redirect()->route('jalur.joint.import.index')
                ->with('success', 'Sync selesai: ' . (empty($parts) ? 'tidak ada perubahan' : implode(', ', $parts)));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Joint sheet sync commit failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal commit: ' . $e->getMessage());
        }
    }

    private function parseJointCode(string $nomorJoint): ?string
    {
        // Format 1: {CLUSTER}-{FITTING}{CODE} or {CLUSTER}-{FITTING}.{CODE}
        // e.g. KRG-CP001 → '001', SPD-ECP.033 → '033', GDK-EL90124 → '124'
        if (preg_match('/^([A-Z0-9\-]+)-([A-Z]+\d*)[\.\-]?(\d{3,})$/', $nomorJoint, $m)) {
            return $m[3];
        }
        // Format 2 (diameter 180): BF.46, EF.010 → full string
        if (preg_match('/^([A-Z]+)[\.\-]?(\d{1,})$/', $nomorJoint, $m)) {
            return $m[0];
        }
        return null;
    }

    public function sheetSyncProcess(Request $request)
    {
        try {
            DB::beginTransaction();

            $fittingMap = JalurFittingType::all()->mapWithKeys(
                fn($f) => [strtoupper($f->nama_fitting) => $f->id]
            );

            $countNew     = 0;
            $countUpdated = 0;
            $countSkipped = 0;

            foreach ($request->input('sync_new', []) as $item) {
                $item = is_string($item) ? json_decode($item, true) : $item;
                if (!$item || empty($item['cluster_id'])) continue;

                JalurJointData::create([
                    'cluster_id'        => $item['cluster_id'],
                    'fitting_type_id'   => $item['fitting_type_id'] ?? null,
                    'nomor_joint'       => $item['nomor_joint'],
                    'tanggal_joint'     => $item['tanggal_joint'],
                    'joint_line_from'   => $item['joint_line_from'] ?? null,
                    'joint_line_to'     => $item['joint_line_to'] ?? null,
                    'tipe_penyambungan' => $item['tipe_penyambungan'] ?? null,
                    'diameter'          => $item['diameter'] ?? null,
                    'status_laporan'    => 'draft',
                    'created_by'        => Auth::id(),
                    'updated_by'        => Auth::id(),
                ]);
                $countNew++;
            }

            foreach ($request->input('sync_updated', []) as $item) {
                $item = is_string($item) ? json_decode($item, true) : $item;
                if (!$item || !isset($item['existing_id'])) continue;

                $existing = JalurJointData::find($item['existing_id']);
                if (!$existing) continue;

                if (in_array($existing->status_laporan, ['acc_tracer', 'acc_cgp'])) {
                    $countSkipped++;
                    continue;
                }

                $d          = $item['data'];
                $fittingId  = isset($d['fitting_name']) ? ($fittingMap[strtoupper($d['fitting_name'])] ?? $existing->fitting_type_id) : $existing->fitting_type_id;

                $existing->update([
                    'tanggal_joint'     => $d['tanggal_joint']     ?? $existing->tanggal_joint,
                    'joint_line_from'   => $d['joint_line_from']   ?? $existing->joint_line_from,
                    'joint_line_to'     => $d['joint_line_to']     ?? $existing->joint_line_to,
                    'tipe_penyambungan' => $d['tipe_penyambungan'] ?? $existing->tipe_penyambungan,
                    'diameter'          => $d['diameter']          ?? $existing->diameter,
                    'fitting_type_id'   => $fittingId,
                    'updated_by'        => Auth::id(),
                ]);
                $countUpdated++;
            }

            DB::commit();

            $parts = [];
            if ($countNew > 0)     $parts[] = "{$countNew} data baru";
            if ($countUpdated > 0) $parts[] = "{$countUpdated} data diupdate";
            if ($countSkipped > 0) $parts[] = "{$countSkipped} data dilindungi (approved)";

            return response()->json([
                'success'       => true,
                'message'       => 'Sync selesai: ' . (empty($parts) ? 'tidak ada perubahan' : implode(', ', $parts)),
                'count_new'     => $countNew,
                'count_updated' => $countUpdated,
                'count_skipped' => $countSkipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Joint sheet sync process failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan semua grup duplikat yang ada di database.
     * Duplikat = >1 record dengan nomor_joint + tanggal_joint + joint_line_from + joint_line_to yang sama.
     */
    public function duplicates()
    {
        $groups = JalurJointData::select(
                'nomor_joint', 'tanggal_joint', 'joint_line_from', 'joint_line_to',
                DB::raw('COUNT(*) as total'),
                DB::raw('GROUP_CONCAT(id ORDER BY created_at ASC) as ids')
            )
            ->groupBy('nomor_joint', 'tanggal_joint', 'joint_line_from', 'joint_line_to')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->get();

        // Untuk setiap grup, ambil detail lengkap tiap record
        $duplicateGroups = $groups->map(function ($group) {
            $ids     = explode(',', $group->ids);
            $records = JalurJointData::with(['cluster', 'fittingType'])
                ->whereIn('id', $ids)
                ->orderBy('created_at')
                ->get();

            return [
                'key'        => $group->nomor_joint . '|' . $group->tanggal_joint . '|' . $group->joint_line_from . '|' . $group->joint_line_to,
                'nomor_joint'    => $group->nomor_joint,
                'tanggal_joint'  => $group->tanggal_joint,
                'joint_line_from'=> $group->joint_line_from,
                'joint_line_to'  => $group->joint_line_to,
                'total'          => $group->total,
                'records'        => $records,
            ];
        });

        return view('jalur.joint.duplicates', [
            'duplicateGroups' => $duplicateGroups,
            'totalGroups'     => $duplicateGroups->count(),
            'totalRecords'    => $duplicateGroups->sum('total'),
        ]);
    }

    /**
     * Resolve duplikat: pertahankan satu record, hapus sisanya.
     */
    public function resolveDuplicates(Request $request)
    {
        $request->validate([
            'keep_id'    => 'required|integer|exists:jalur_joint_data,id',
            'delete_ids' => 'required|array|min:1',
            'delete_ids.*'=> 'integer|exists:jalur_joint_data,id',
        ]);

        $keepId    = $request->input('keep_id');
        $deleteIds = $request->input('delete_ids');

        // Pastikan keep_id tidak ada di delete_ids
        if (in_array($keepId, $deleteIds)) {
            return back()->withErrors(['keep_id' => 'Record yang dipertahankan tidak boleh ikut dihapus.']);
        }

        DB::beginTransaction();
        try {
            $deleted = JalurJointData::whereIn('id', $deleteIds)->delete();

            DB::commit();

            Log::info('Joint duplicates resolved', [
                'kept_id'     => $keepId,
                'deleted_ids' => $deleteIds,
                'deleted_count' => $deleted,
            ]);

            return redirect()->route('jalur.joint.import.duplicates')
                ->with('success', "Duplikat diselesaikan: {$deleted} record dihapus, 1 record dipertahankan.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resolve joint duplicates', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Gagal menghapus duplikat: ' . $e->getMessage()]);
        }
    }
}
