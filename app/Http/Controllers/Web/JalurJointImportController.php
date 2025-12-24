<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Imports\JalurJointImport;
use App\Exports\JalurJointTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            // Use move instead of storeAs for better reliability
            $destinationPath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;
            $saved = copy($filePath, $destinationPath);

            Log::info('File save attempt', [
                'source' => $filePath,
                'destination' => $destinationPath,
                'saved' => $saved,
                'file_exists_after_save' => file_exists($destinationPath),
                'directory_writable' => is_writable($tempDir),
            ]);

            if (!$saved || !file_exists($destinationPath)) {
                throw new \Exception('Gagal menyimpan file temporary. Silakan coba lagi.');
            }

            // Dry run mode using the saved temp file (not the uploaded file)
            $import = new JalurJointImport(true, $destinationPath);
            Excel::import($import, $destinationPath);

            $results = $import->getResults();

            return view('jalur.joint.import-preview', [
                'results' => $results,
                'fileName' => $file->getClientOriginalName(),
                'tempFilePath' => 'temp-imports/' . $tempFileName, // Relative path from storage/app
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();

            return back()->withErrors([
                'file' => 'Validasi Excel gagal. Periksa format file Anda.'
            ])->with('validation_failures', $failures);

        } catch (\Exception $e) {
            Log::error('Joint import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'file' => 'Gagal memproses file: ' . $e->getMessage()
            ]);
        }
    }

    public function import(Request $request)
    {
        Log::info('Joint import execute started', [
            'has_temp_file' => $request->has('temp_file_path'),
            'has_file_upload' => $request->hasFile('file'),
        ]);

        // Check if coming from preview (with temp file) or direct upload
        if ($request->has('temp_file_path')) {
            // Import from temp file (from preview)
            $relativePath = $request->input('temp_file_path');
            $tempFilePath = storage_path('app') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            Log::info('Checking temp file', [
                'path' => $tempFilePath,
                'exists' => file_exists($tempFilePath),
            ]);

            if (!file_exists($tempFilePath)) {
                Log::error('Temp file not found', ['path' => $tempFilePath]);
                return redirect()->route('jalur.joint.import.index')
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

            // Real import mode
            $import = new JalurJointImport(false, is_string($file) ? $file : $file->getRealPath());
            Excel::import($import, $file);

            $results = $import->getResults();

            // Count success and failures
            $successCount = collect($results)->where('status', 'success')->count();
            $failCount = collect($results)->where('status', 'error')->count();

            Log::info('Joint import completed', [
                'success' => $successCount,
                'failed' => $failCount,
                'total' => count($results),
            ]);

            // Clean up temp file if exists
            if ($request->has('temp_file_path') && isset($tempFilePath) && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
                Log::info('Temp file deleted', ['path' => $tempFilePath]);
            }

            if ($failCount > 0) {
                $failedRows = collect($results)->where('status', 'error');

                Log::warning('Joint import has failures', ['failed_count' => $failCount]);

                return redirect()->route('jalur.joint.import.index')
                    ->with('warning', "Import selesai dengan {$successCount} data berhasil dan {$failCount} data gagal.")
                    ->with('failed_rows', $failedRows);
            }

            Log::info('Joint import all success, redirecting to joint index');

            return redirect()->route('jalur.joint.index')
                ->with('success', "Berhasil import {$successCount} data joint!");

        } catch (\Exception $e) {
            Log::error('Joint import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'file' => 'Import gagal: ' . $e->getMessage()
            ]);
        }
    }
}
