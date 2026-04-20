<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurLoweringImport;
use App\Exports\JalurLoweringTemplateExport;
use App\Models\JalurLoweringData;
use App\Models\PhotoApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
