<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleSheetSettingsService
{
    private function getFullPath(): string
    {
        return storage_path('app/google/google_sheet_settings.json');
    }

    /**
     * Retrieve current settings from JSON.
     */
    public function getSettings(): array
    {
        $path = $this->getFullPath();

        if (!file_exists($path)) {
            return $this->getDefaultSettings();
        }

        $content = file_get_contents($path);
        $settings = json_decode($content, true);

        return is_array($settings) ? array_merge($this->getDefaultSettings(), $settings) : $this->getDefaultSettings();
    }

    /**
     * Update and save new settings to JSON.
     */
    public function updateSettings(array $newSettings): bool
    {
        $currentSettings = $this->getSettings();
        // Only merge fields that exist in the default schema to prevent bloat
        $schemaKeys = array_keys($this->getDefaultSettings());
        
        foreach ($newSettings as $key => $value) {
            if (in_array($key, $schemaKeys)) {
                $currentSettings[$key] = $value;
            }
        }

        try {
            $path = $this->getFullPath();
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($path, json_encode($currentSettings, JSON_PRETTY_PRINT));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save Google Sheets settings', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Mark a successful automatic sync.
     */
    public function markSyncSuccess(): void
    {
        $this->updateSettings([
            'last_synced_at' => now()->toDateTimeString(),
            'status_message' => 'Sinkronisasi otomatis berhasil (' . now()->timezone('Asia/Jakarta')->format('H:i') . ' WIB)'
        ]);
    }

    /**
     * Mark a failed automatic sync.
     */
    public function markSyncFailed(string $error): void
    {
        $this->updateSettings([
            'status_message' => 'Gagal (' . now()->timezone('Asia/Jakarta')->format('H:i') . ' WIB): ' . substr($error, 0, 50)
        ]);
    }

    /**
     * Defines the standard structure and fallbacks.
     */
    private function getDefaultSettings(): array
    {
        return [
            'spreadsheet_id' => config('services.google_sheets.spreadsheet_id') ?? '',
            'sheet_name' => config('services.google_sheets.range') ? explode('!', config('services.google_sheets.range'))[0] : 'Recap AERGAS',
            'start_row' => 5,
            'auto_sync_enabled' => false,
            'sync_mode' => 'interval',
            'sync_time' => '00:00',
            'sync_interval_minutes' => 60,
            'last_synced_at' => null,
            'status_message' => 'Belum pernah sinkronisasi otomatis'
        ];
    }
}
