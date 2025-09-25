<?php

namespace App\Console\Commands;

use App\Models\CalonPelanggan;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use App\Models\PhotoApproval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class ResetToTracerReviewCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'aergas:reset-to-tracer-review
                            {reff_id : Reference ID pelanggan}
                            {--module=* : Module yang akan direset (sk,sr,gas_in). Default: semua module}
                            {--force : Force reset tanpa konfirmasi}';

    /**
     * The console command description.
     */
    protected $description = 'Reset status module tertentu kembali ke tracer_review untuk testing atau rollback approval';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reffId = $this->argument('reff_id');
        $modules = $this->option('module');
        $force = $this->option('force');

        // Validasi customer exists
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        if (!$customer) {
            $this->error("❌ Customer dengan Reff ID '{$reffId}' tidak ditemukan!");
            return 1;
        }

        // Default modules jika tidak specified
        if (empty($modules)) {
            $modules = ['sk', 'sr', 'gas_in'];
        }

        $this->info("🔍 Customer: {$customer->nama_pelanggan} ({$reffId})");
        $this->info("📋 Modules yang akan direset: " . implode(', ', $modules));

        // Show current status
        $this->showCurrentStatus($reffId, $modules);

        // Confirmation
        if (!$force && !$this->confirm('⚠️  Apakah Anda yakin ingin mereset status ke tracer_review?')) {
            $this->info('❌ Operasi dibatalkan.');
            return 0;
        }

        // Process each module
        $resetCount = 0;
        $errors = [];

        foreach ($modules as $module) {
            try {
                $result = $this->resetModuleStatus($reffId, strtolower($module));
                if ($result['success']) {
                    $resetCount++;
                    $this->info("✅ {$result['module']}: {$result['message']}");
                } else {
                    $errors[] = "{$result['module']}: {$result['message']}";
                    $this->error("❌ {$result['module']}: {$result['message']}");
                }
            } catch (Exception $e) {
                $errors[] = "{$module}: {$e->getMessage()}";
                $this->error("❌ Error pada {$module}: {$e->getMessage()}");
            }
        }

        // Summary
        $this->newLine();
        $this->info("🎯 Reset selesai!");
        $this->info("✅ Berhasil: {$resetCount} module(s)");
        if (!empty($errors)) {
            $this->error("❌ Error: " . count($errors) . " module(s)");
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
        }

        // Show final status
        $this->newLine();
        $this->info("📊 Status setelah reset:");
        $this->showCurrentStatus($reffId, $modules);

        return empty($errors) ? 0 : 1;
    }

    /**
     * Show current module status
     */
    private function showCurrentStatus(string $reffId, array $modules): void
    {
        foreach ($modules as $module) {
            $model = $this->getModuleModel($reffId, strtolower($module));
            if ($model) {
                $moduleStatus = $model->module_status ?? 'not_started';
                $photoStatus = $model->overall_photo_status ?? 'draft';
                $tracerAt = $model->tracer_approved_at ? $model->tracer_approved_at->format('d/m/Y H:i') : '-';
                $cgpAt = $model->cgp_approved_at ? $model->cgp_approved_at->format('d/m/Y H:i') : '-';

                $this->table(
                    ['Field', 'Value'],
                    [
                        [strtoupper($module) . ' Module Status', $moduleStatus],
                        ['Photo Status', $photoStatus],
                        ['Tracer Approved At', $tracerAt],
                        ['CGP Approved At', $cgpAt],
                    ]
                );
            } else {
                $this->line("   - " . strtoupper($module) . ": tidak ditemukan");
            }
        }
    }

    /**
     * Reset module status to tracer_review
     */
    private function resetModuleStatus(string $reffId, string $module): array
    {
        return DB::transaction(function () use ($reffId, $module) {
            $model = $this->getModuleModel($reffId, $module);

            if (!$model) {
                return [
                    'success' => false,
                    'module' => strtoupper($module),
                    'message' => 'Data module tidak ditemukan'
                ];
            }

            $oldStatus = $model->module_status;

            // Reset module fields
            $model->update([
                'module_status' => 'tracer_review',
                'overall_photo_status' => 'tracer_review',
                'cgp_approved_at' => null,
                'cgp_approved_by' => null,
                'cgp_notes' => null,
                'updated_at' => now(),
            ]);

            // Reset photo approvals to tracer_review
            PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', strtoupper($module))
                ->update([
                    'status' => 'tracer_review',
                    'cgp_status' => null,
                    'cgp_user_id' => null,
                    'cgp_approved_at' => null,
                    'cgp_notes' => null,
                    'cgp_rejected_at' => null,
                    'updated_at' => now(),
                ]);

            return [
                'success' => true,
                'module' => strtoupper($module),
                'message' => "Status direset dari '{$oldStatus}' ke 'tracer_review'"
            ];
        });
    }

    /**
     * Get model instance based on module
     */
    private function getModuleModel(string $reffId, string $module)
    {
        return match (strtolower($module)) {
            'sk' => SkData::where('reff_id_pelanggan', $reffId)->first(),
            'sr' => SrData::where('reff_id_pelanggan', $reffId)->first(),
            'gas_in' => GasInData::where('reff_id_pelanggan', $reffId)->first(),
            default => null,
        };
    }
}