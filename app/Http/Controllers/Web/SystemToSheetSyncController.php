<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoogleSheetService;
use App\Services\GoogleSyncRunnerService;
use App\Services\GoogleSheetSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemToSheetSyncController extends Controller
{
    private GoogleSheetService $sheetService;
    private GoogleSyncRunnerService $runnerService;
    private GoogleSheetSettingsService $settingsService;

    public function __construct(
        GoogleSheetService $sheetService,
        GoogleSyncRunnerService $runnerService,
        GoogleSheetSettingsService $settingsService
    ) {
        $this->sheetService = $sheetService;
        $this->runnerService = $runnerService;
        $this->settingsService = $settingsService;
    }

    public function index()
    {
        $serviceAccountEmail = $this->sheetService->getServiceAccountEmail();
        $settings = $this->settingsService->getSettings();

        return view('admin.sync-sheets.index', compact('serviceAccountEmail', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'spreadsheet_id' => 'required|string',
            'sheet_name' => 'required|string',
            'start_row' => 'required|integer|min:2',
            'sync_mode' => 'required|in:interval,daily',
            'sync_time' => 'required|string',
            'sync_interval_minutes' => 'required|integer|min:1',
            'auto_sync_enabled' => 'nullable'
        ]);

        $this->settingsService->updateSettings([
            'spreadsheet_id' => $request->input('spreadsheet_id'),
            'sheet_name' => $request->input('sheet_name'),
            'start_row' => (int) $request->input('start_row'),
            'sync_mode' => $request->input('sync_mode'),
            'sync_time' => $request->input('sync_time'),
            'sync_interval_minutes' => $request->input('sync_interval_minutes'),
            'auto_sync_enabled' => $request->has('auto_sync_enabled')
        ]);

        return back()->with('success', "Pengaturan berhasil disimpan.");
    }

    public function sync(Request $request)
    {
        $settings = $this->settingsService->getSettings();
        $spreadsheetId = $settings['spreadsheet_id'] ?? '';
        $sheetName = $settings['sheet_name'] ?? '';
        $startRow = (int) ($settings['start_row'] ?? 5);

        if (empty($spreadsheetId) || empty($sheetName)) {
            return back()->with('error', "Harap simpan Spreadsheet ID dan Nama Tab Sheet di Pengaturan terlebih dahulu.");
        }

        try {
            $totalSynced = $this->runnerService->execute($spreadsheetId, $sheetName, $startRow);
            $this->settingsService->markSyncSuccess();
            return back()->with('success', "Sinkronisasi Manual berhasil! Total $totalSynced baris data.");

        } catch (\Exception $e) {
            Log::error('Sync failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            $this->settingsService->markSyncFailed($e->getMessage());
            $shortError = substr($e->getMessage(), 0, 1000);
            return back()->with('error', 'Gagal: ' . $shortError);
        }
    }
}
