<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurJointImport;
use App\Exports\JalurJointTemplateExport;
use App\Models\JalurJointData;
use Illuminate\Http\Request;
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
