<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurMc0Import;
use App\Exports\JalurMc0TemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class JalurMc0ImportController extends Controller
{
    /**
     * Show import form
     */
    public function index()
    {
        return view('jalur.mc0.import');
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new JalurMc0TemplateExport(),
            'template_jalur_mc0_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Preview import (dry run)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();

            // Ensure temp-imports directory exists
            $tempDir = storage_path('app/temp-imports');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
                Log::info('Created temp-imports directory', ['path' => $tempDir]);
            }

            // Save file to temp storage for later use with unique name
            $tempFileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

            // Use copy instead of storeAs for better reliability
            $destinationPath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;
            $saved = copy($filePath, $destinationPath);

            Log::info('MC-0 file save attempt', [
                'source' => $filePath,
                'destination' => $destinationPath,
                'saved' => $saved,
                'file_exists_after_save' => file_exists($destinationPath),
                'directory_writable' => is_writable($tempDir),
            ]);

            if (!$saved || !file_exists($destinationPath)) {
                throw new \Exception('Gagal menyimpan file temporary. Silakan coba lagi.');
            }

            // Dry run mode using the saved temp file
            $import = new JalurMc0Import(true);
            Excel::import($import, $destinationPath);

            $results = $import->getResults();

            return view('jalur.mc0.import-preview', [
                'results' => $results,
                'fileName' => $file->getClientOriginalName(),
                'tempFilePath' => 'temp-imports/' . $tempFileName, // Relative path from storage/app
            ]);

        } catch (\Exception $e) {
            Log::error('MC-0 import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Gagal memproses file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Execute import
     */
    public function import(Request $request)
    {
        // Check if coming from preview (with temp file) or direct upload
        if ($request->has('temp_file_path')) {
            // Import from temp file (from preview)
            $tempFilePath = storage_path('app/' . $request->input('temp_file_path'));

            if (!file_exists($tempFilePath)) {
                return redirect()
                    ->route('jalur.mc0.import.index')
                    ->with('error', 'File temporary tidak ditemukan. Silakan upload ulang.');
            }

            $file = $tempFilePath;
        } else {
            // Direct upload
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
            ]);

            $file = $request->file('file');
        }

        try {
            // Set execution time limit for large imports
            set_time_limit(600); // 10 minutes for large imports
            ini_set('memory_limit', '512M'); // Increase memory limit

            // Disable query log to improve performance
            DB::connection()->disableQueryLog();

            // Execute import
            $import = new JalurMc0Import(false);
            Excel::import($import, $file);

            // Delete temp file if exists
            if ($request->has('temp_file_path') && isset($tempFilePath) && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            $results = $import->getResults();

            // Prepare summary message
            $message = "Import selesai: {$results['success']} data berhasil";

            if ($results['updated'] > 0) {
                $message .= " ({$results['updated']} data di-update)";
            }

            if ($results['skipped'] > 0) {
                $message .= ", {$results['skipped']} baris dikosongkan";
            }

            if (!empty($results['failed'])) {
                $message .= ", " . count($results['failed']) . " baris gagal";
            }

            return redirect()
                ->route('jalur.mc0.import.index')
                ->with('success', $message)
                ->with('import_results', $results);

        } catch (\Exception $e) {
            Log::error('MC-0 import execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Gagal mengimport data: ' . $e->getMessage())
                ->withInput();
        }
    }
}
