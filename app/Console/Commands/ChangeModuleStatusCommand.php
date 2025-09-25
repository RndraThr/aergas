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

class ChangeModuleStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'aergas:change-module-status
                            {reff_id : Reference ID pelanggan}
                            {status : Target status (draft|ai_validation|tracer_review|cgp_review|completed|rejected)}
                            {--module=* : Module yang akan diubah (sk,sr,gas_in). Default: semua module}
                            {--force : Force change tanpa konfirmasi}';

    /**
     * The console command description.
     */
    protected $description = 'Mengubah status module ke status tertentu untuk testing atau rollback';

    /**
     * Available statuses
     */
    private const VALID_STATUSES = [
        'draft',
        'ai_validation',
        'tracer_review',
        'cgp_review',
        'completed',
        'rejected'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reffId = $this->argument('reff_id');
        $targetStatus = $this->argument('status');
        $modules = $this->option('module');
        $force = $this->option('force');

        // Validasi status
        if (!in_array($targetStatus, self::VALID_STATUSES)) {
            $this->error("❌ Status tidak valid. Pilihan: " . implode(', ', self::VALID_STATUSES));
            return 1;
        }

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
        $this->info("📋 Modules: " . implode(', ', $modules));
        $this->info("🎯 Target Status: {$targetStatus}");

        // Show current status
        $this->newLine();
        $this->info("📊 Status saat ini:");
        $this->showCurrentStatus($reffId, $modules);

        // Confirmation
        if (!$force && !$this->confirm("⚠️  Apakah Anda yakin ingin mengubah status ke '{$targetStatus}'?")) {
            $this->info('❌ Operasi dibatalkan.');
            return 0;
        }

        // Process each module
        $changeCount = 0;
        $errors = [];

        foreach ($modules as $module) {
            try {
                $result = $this->changeModuleStatus($reffId, strtolower($module), $targetStatus);
                if ($result['success']) {
                    $changeCount++;
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
        $this->info("🎯 Perubahan selesai!");
        $this->info("✅ Berhasil: {$changeCount} module(s)");
        if (!empty($errors)) {
            $this->error("❌ Error: " . count($errors) . " module(s)");
        }

        // Show final status
        $this->newLine();
        $this->info("📊 Status setelah perubahan:");
        $this->showCurrentStatus($reffId, $modules);

        return empty($errors) ? 0 : 1;
    }

    /**
     * Show current module status
     */
    private function showCurrentStatus(string $reffId, array $modules): void
    {
        $headers = ['Module', 'Module Status', 'Photo Status', 'Tracer', 'CGP'];
        $rows = [];

        foreach ($modules as $module) {
            $model = $this->getModuleModel($reffId, strtolower($module));
            if ($model) {
                $moduleStatus = $model->module_status ?? 'not_started';
                $photoStatus = $model->overall_photo_status ?? 'draft';
                $tracerAt = $model->tracer_approved_at ? '✅ ' . $model->tracer_approved_at->format('d/m H:i') : '❌';
                $cgpAt = $model->cgp_approved_at ? '✅ ' . $model->cgp_approved_at->format('d/m H:i') : '❌';

                $rows[] = [
                    strtoupper($module),
                    $moduleStatus,
                    $photoStatus,
                    $tracerAt,
                    $cgpAt
                ];
            } else {
                $rows[] = [strtoupper($module), 'tidak ditemukan', '-', '-', '-'];
            }
        }

        $this->table($headers, $rows);
    }

    /**
     * Change module status
     */
    private function changeModuleStatus(string $reffId, string $module, string $targetStatus): array
    {
        return DB::transaction(function () use ($reffId, $module, $targetStatus) {
            $model = $this->getModuleModel($reffId, $module);

            if (!$model) {
                return [
                    'success' => false,
                    'module' => strtoupper($module),
                    'message' => 'Data module tidak ditemukan'
                ];
            }

            $oldStatus = $model->module_status;

            // Prepare update data based on target status
            $updateData = [
                'module_status' => $targetStatus,
                'overall_photo_status' => $targetStatus,
                'updated_at' => now(),
            ];

            // Reset approval fields based on target status
            switch ($targetStatus) {
                case 'draft':
                case 'ai_validation':
                    $updateData = array_merge($updateData, [
                        'tracer_approved_at' => null,
                        'tracer_approved_by' => null,
                        'tracer_notes' => null,
                        'cgp_approved_at' => null,
                        'cgp_approved_by' => null,
                        'cgp_notes' => null,
                    ]);
                    break;

                case 'tracer_review':
                    $updateData = array_merge($updateData, [
                        'cgp_approved_at' => null,
                        'cgp_approved_by' => null,
                        'cgp_notes' => null,
                    ]);
                    break;

                case 'cgp_review':
                    if (!$model->tracer_approved_at) {
                        $updateData['tracer_approved_at'] = now();
                        $updateData['tracer_approved_by'] = 1; // Default user
                    }
                    break;

                case 'completed':
                    if (!$model->tracer_approved_at) {
                        $updateData['tracer_approved_at'] = now();
                        $updateData['tracer_approved_by'] = 1;
                    }
                    if (!$model->cgp_approved_at) {
                        $updateData['cgp_approved_at'] = now();
                        $updateData['cgp_approved_by'] = 1;
                    }
                    break;
            }

            // Update module
            $model->update($updateData);

            // Update photo approvals
            $photoUpdate = [
                'status' => $targetStatus,
                'updated_at' => now(),
            ];

            // Reset photo approval fields based on target status
            switch ($targetStatus) {
                case 'draft':
                case 'ai_validation':
                    $photoUpdate = array_merge($photoUpdate, [
                        'tracer_status' => null,
                        'tracer_user_id' => null,
                        'tracer_approved_at' => null,
                        'tracer_notes' => null,
                        'cgp_status' => null,
                        'cgp_user_id' => null,
                        'cgp_approved_at' => null,
                        'cgp_notes' => null,
                        'cgp_rejected_at' => null,
                    ]);
                    break;

                case 'tracer_review':
                    $photoUpdate = array_merge($photoUpdate, [
                        'cgp_status' => null,
                        'cgp_user_id' => null,
                        'cgp_approved_at' => null,
                        'cgp_notes' => null,
                        'cgp_rejected_at' => null,
                    ]);
                    break;
            }

            PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', strtoupper($module))
                ->update($photoUpdate);

            return [
                'success' => true,
                'module' => strtoupper($module),
                'message' => "Status berubah dari '{$oldStatus}' ke '{$targetStatus}'"
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