<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Exception;

class TestGoogleDriveCommand extends Command
{
    protected $signature = 'test:google-drive {--detailed}';
    protected $description = 'Test Google Drive service connection and configuration';

    public function handle()
    {
        $this->info('Testing Google Drive Service...');
        $this->newLine();

        // Test 1: Check environment variables
        $this->info('=== 1. Environment Configuration ===');
        $configs = [
            'GOOGLE_DRIVE_CLIENT_ID' => config('services.google_drive.client_id'),
            'GOOGLE_DRIVE_CLIENT_SECRET' => config('services.google_drive.client_secret'),
            'GOOGLE_DRIVE_REFRESH_TOKEN' => config('services.google_drive.refresh_token'),
            'GOOGLE_DRIVE_FOLDER_ID' => config('services.google_drive.folder_id'),
            'SERVICE_ACCOUNT_JSON' => config('services.google_drive.service_account_json'),
        ];

        foreach ($configs as $key => $value) {
            if ($value) {
                if ($key === 'GOOGLE_DRIVE_CLIENT_SECRET' || $key === 'GOOGLE_DRIVE_REFRESH_TOKEN') {
                    $masked = substr($value, 0, 10) . '...' . substr($value, -5);
                    $this->line("✓ {$key}: {$masked}");
                } else {
                    $this->line("✓ {$key}: {$value}");
                }
            } else {
                $this->error("✗ {$key}: NOT SET");
            }
        }
        $this->newLine();

        // Test 2: Try to instantiate GoogleDriveService
        $this->info('=== 2. Service Instantiation ===');
        try {
            $service = new GoogleDriveService();
            $this->line('✓ GoogleDriveService instantiated successfully');
        } catch (Exception $e) {
            $this->error('✗ Failed to instantiate GoogleDriveService:');
            $this->error($e->getMessage());

            if ($this->option('detailed')) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            return 1;
        }

        // Test 3: Test connection
        $this->info('=== 3. Connection Test ===');
        try {
            $result = $service->testConnection();

            if ($result['success']) {
                $this->line('✓ Connected to Google Drive successfully');
                if (isset($result['user_email'])) {
                    $this->line("  User: {$result['user_email']}");
                }
                if (isset($result['storage_used']) && isset($result['storage_limit'])) {
                    $used = $this->formatBytes($result['storage_used']);
                    $limit = $result['storage_limit'] ? $this->formatBytes($result['storage_limit']) : 'Unlimited';
                    $this->line("  Storage: {$used} / {$limit}");
                }
            } else {
                $this->error('✗ Connection failed:');
                $this->error($result['message']);
            }
        } catch (Exception $e) {
            $this->error('✗ Connection test failed:');
            $this->error($e->getMessage());

            if ($this->option('detailed')) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
        }

        // Test 4: Test folder access
        $this->newLine();
        $this->info('=== 4. Folder Access Test ===');
        try {
            $folderId = $service->ensureNestedFolders('test_connection');
            $this->line("✓ Successfully accessed/created test folder: {$folderId}");
        } catch (Exception $e) {
            $this->error('✗ Folder access failed:');
            $this->error($e->getMessage());
        }

        // Test 5: Storage stats
        $this->newLine();
        $this->info('=== 5. Storage Statistics ===');
        try {
            $stats = $service->getStorageStats();
            $this->line("Used: {$stats['used_human']}");
            $this->line("Limit: " . ($stats['limit'] ? $this->formatBytes($stats['limit']) : 'Unlimited'));
        } catch (Exception $e) {
            $this->error('✗ Storage stats failed:');
            $this->error($e->getMessage());
        }

        $this->newLine();
        $this->info('Google Drive test completed.');
        return 0;
    }

    private function formatBytes($bytes)
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }
}
