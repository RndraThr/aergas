<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{GasInData, SkData, SrData, PhotoApproval};
use Illuminate\Support\Facades\DB;

class FixDuplicateGasInCommand extends Command
{
    protected $signature = 'aergas:fix-duplicates {--dry-run : Only show what would be done} {--module=* : Specific modules to fix (sk,sr,gas_in)}';

    protected $description = 'Fix duplicate entries in all modules and reset approval status';

    private array $moduleConfig = [
        'sk' => ['table' => 'sk_data', 'model' => SkData::class, 'module_name' => 'sk'],
        'sr' => ['table' => 'sr_data', 'model' => SrData::class, 'module_name' => 'sr'],
        'gas_in' => ['table' => 'gas_in_data', 'model' => GasInData::class, 'module_name' => 'gas_in'],
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $moduleFilter = $this->option('module');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Determine which modules to process
        $modulesToProcess = empty($moduleFilter) 
            ? array_keys($this->moduleConfig)
            : array_intersect($moduleFilter, array_keys($this->moduleConfig));

        if (empty($modulesToProcess)) {
            $this->error('❌ No valid modules specified. Available: ' . implode(', ', array_keys($this->moduleConfig)));
            return 1;
        }

        $this->info('🔎 Processing modules: ' . implode(', ', $modulesToProcess));

        foreach ($modulesToProcess as $moduleKey) {
            $this->processModule($moduleKey, $dryRun);
        }

        if (!$dryRun) {
            $this->info("\n✅ All cleanup completed!");
        } else {
            $this->info("\n💡 Run without --dry-run to apply changes");
        }
    }

    private function processModule(string $moduleKey, bool $dryRun): void
    {
        $config = $this->moduleConfig[$moduleKey];
        $tableName = $config['table'];
        $moduleName = strtoupper($moduleKey);

        $this->line("\n" . str_repeat('=', 60));
        $this->info("🔍 Checking {$moduleName} module ({$tableName})");
        $this->line(str_repeat('=', 60));

        // Find duplicates
        $duplicates = DB::select("
            SELECT reff_id_pelanggan, COUNT(*) as count 
            FROM {$tableName} 
            WHERE deleted_at IS NULL 
            GROUP BY reff_id_pelanggan 
            HAVING count > 1
        ");

        if (empty($duplicates)) {
            $this->info("✅ No duplicates found in {$moduleName}");
            return;
        }

        $this->warn("⚠️  Found " . count($duplicates) . " duplicate groups in {$moduleName}:");

        foreach ($duplicates as $duplicate) {
            $this->processDuplicateGroup($config, $duplicate, $dryRun);
        }
    }

    private function processDuplicateGroup(array $config, object $duplicate, bool $dryRun): void
    {
        $reffId = $duplicate->reff_id_pelanggan;
        $modelClass = $config['model'];
        $moduleName = $config['module_name'];
        
        $this->line("\n📋 Processing reff_id: {$reffId} ({$duplicate->count} records)");

        // Get all records for this reff_id, ordered by creation date
        $records = $modelClass::where('reff_id_pelanggan', $reffId)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->table(['ID', 'Status', 'Tracer Approved', 'CGP Approved', 'Created At'], 
            $records->map(fn($r) => [
                $r->id,
                $r->status,
                $r->tracer_approved_at ? '✅' : '❌',
                $r->cgp_approved_at ? '✅' : '❌',
                $r->created_at->format('Y-m-d H:i:s')
            ])->toArray()
        );

        if ($records->count() > 1) {
            $keepRecord = $records->first();
            $deleteRecords = $records->skip(1);
            
            $this->info("✅ Keeping: ID {$keepRecord->id} (oldest)");
            
            foreach ($deleteRecords as $deleteRecord) {
                $this->warn("🗑️  Will delete: ID {$deleteRecord->id}");
                
                if (!$dryRun) {
                    // Soft delete the record
                    $deleteRecord->delete();
                    
                    // Also clean up related photo approvals for the newer records
                    $deletedPhotos = PhotoApproval::where('reff_id_pelanggan', $reffId)
                        ->where('module_name', $moduleName)
                        ->where('created_at', '>=', $deleteRecord->created_at)
                        ->get();
                        
                    foreach ($deletedPhotos as $photo) {
                        $photo->delete(); // Hard delete (no soft deletes in photo_approvals)
                        $this->line("  📸 Deleted related photo: {$photo->photo_field_name}");
                    }
                }
            }

            // Reset approval status on the kept record if it was corrupted
            if (!$dryRun && $this->shouldResetApprovalStatus($keepRecord)) {
                $this->resetApprovalStatus($keepRecord);
            }
        }
    }

    private function shouldResetApprovalStatus(GasInData $gasIn): bool
    {
        // Check if approval status doesn't match photo approval status
        $approvedPhotos = $gasIn->photoApprovals()
            ->where('photo_status', 'cgp_approved')
            ->count();
            
        $totalPhotos = $gasIn->photoApprovals()->count();
        
        // If we have tracer/cgp approval but no proper photo approvals, it's inconsistent
        return ($gasIn->tracer_approved_at || $gasIn->cgp_approved_at) && 
               ($totalPhotos === 0 || $approvedPhotos < $totalPhotos);
    }

    private function resetApprovalStatus(GasInData $gasIn): void
    {
        $originalStatus = $gasIn->status;
        
        $gasIn->update([
            'status' => GasInData::STATUS_DRAFT,
            'tracer_approved_at' => null,
            'tracer_approved_by' => null,
            'tracer_notes' => null,
            'cgp_approved_at' => null,
            'cgp_approved_by' => null,
            'cgp_notes' => null,
        ]);
        
        $this->warn("🔄 Reset approval status for ID {$gasIn->id}: {$originalStatus} → draft");
    }
}