<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Services\{
    GoogleDriveService,
    OpenAIService,
    TelegramService,
    NotificationService,
    PhotoApprovalService,
    FileUploadService
};
use Exception;

class TestAllServicesCommand extends Command
{
    protected $signature = 'test:all-services {--detailed}';
    protected $description = 'Test all application services for admin pages';

    public function handle()
    {
        $this->info('Testing All Services for Admin Pages...');
        $this->newLine();

        $allPassed = true;

        // Test 1: Database Tables
        $this->info('=== 1. Database Tables Check ===');
        $requiredTables = [
            'users', 'notifications', 'file_storages',
            'audit_logs', 'photo_approvals', 'calon_pelanggan'
        ];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("✓ Table '{$table}' exists");
            } else {
                $this->error("✗ Table '{$table}' missing");
                $allPassed = false;
            }
        }
        $this->newLine();

        // Test 2: Google Drive Service
        $this->info('=== 2. Google Drive Service ===');
        try {
            $gdrive = app(GoogleDriveService::class);
            $result = $gdrive->testConnection();
            if ($result['success']) {
                $this->line("✓ Google Drive: Connected");
                if (isset($result['user_email'])) {
                    $this->line("  Email: {$result['user_email']}");
                }
            } else {
                $this->error("✗ Google Drive: {$result['message']}");
                $allPassed = false;
            }
        } catch (Exception $e) {
            $this->error("✗ Google Drive: {$e->getMessage()}");
            $allPassed = false;
        }
        $this->newLine();

        // Test 3: OpenAI Service
        $this->info('=== 3. OpenAI Service ===');
        try {
            $openai = app(OpenAIService::class);
            $result = $openai->testConnection();
            if ($result['success']) {
                $this->line("✓ OpenAI: Connected");
                $this->line("  Model: {$result['model']}");
            } else {
                $this->error("✗ OpenAI: {$result['message']}");
                // OpenAI tidak critical untuk admin pages
            }
        } catch (Exception $e) {
            $this->error("✗ OpenAI: {$e->getMessage()}");
        }
        $this->newLine();

        // Test 4: Telegram Service
        $this->info('=== 4. Telegram Service ===');
        try {
            $telegram = app(TelegramService::class);
            if ($telegram->testConnection()) {
                $this->line("✓ Telegram: Connected");
            } else {
                $this->error("✗ Telegram: Connection failed");
                // Telegram tidak critical untuk admin pages
            }
        } catch (Exception $e) {
            $this->error("✗ Telegram: {$e->getMessage()}");
        }
        $this->newLine();

        // Test 5: Service Dependencies
        $this->info('=== 5. Service Dependencies ===');
        try {
            // Test NotificationService
            $notificationService = app(NotificationService::class);
            $this->line("✓ NotificationService: Instantiated");

            // Test PhotoApprovalService
            $photoService = app(PhotoApprovalService::class);
            $this->line("✓ PhotoApprovalService: Instantiated");

            // Test FileUploadService
            $fileService = app(FileUploadService::class);
            $this->line("✓ FileUploadService: Instantiated");

        } catch (Exception $e) {
            $this->error("✗ Service dependency: {$e->getMessage()}");
            $allPassed = false;

            if ($this->option('detailed')) {
                $this->error("Stack trace:");
                $this->error($e->getTraceAsString());
            }
        }
        $this->newLine();

        // Test 6: Configuration Check
        $this->info('=== 6. Configuration Check ===');
        $configs = [
            'services.google_drive.service_account_json' => 'Google Drive Service Account',
            'services.openai.api_key' => 'OpenAI API Key',
            'services.telegram.bot_token' => 'Telegram Bot Token',
            'app.url' => 'Application URL',
        ];

        foreach ($configs as $key => $label) {
            $value = config($key);
            if ($value) {
                if (str_contains($key, 'key') || str_contains($key, 'token')) {
                    $masked = substr($value, 0, 8) . '...';
                    $this->line("✓ {$label}: {$masked}");
                } else {
                    $this->line("✓ {$label}: {$value}");
                }
            } else {
                $this->error("✗ {$label}: NOT SET");
                if (str_contains($key, 'google_drive')) {
                    $allPassed = false; // Google Drive is critical
                }
            }
        }
        $this->newLine();

        // Test 7: File Permissions
        $this->info('=== 7. File Permissions ===');
        $directories = [
            storage_path('app'),
            storage_path('logs'),
            public_path('storage'),
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $this->line("✓ {$dir}: Writable");
            } else {
                $this->error("✗ {$dir}: Not writable");
                $allPassed = false;
            }
        }

        // Summary
        $this->newLine();
        if ($allPassed) {
            $this->info('🎉 All critical services are working! Admin pages should be accessible.');
        } else {
            $this->error('❌ Some critical services have issues. Please fix before accessing admin pages.');
            return 1;
        }

        return 0;
    }
}
