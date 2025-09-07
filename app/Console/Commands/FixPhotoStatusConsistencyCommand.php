<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{PhotoApproval, SkData, SrData, GasInData};
use Illuminate\Support\Facades\DB;

class FixPhotoStatusConsistencyCommand extends Command
{
    protected $signature = 'aergas:fix-photo-consistency {--dry-run : Only show what would be done} {--reff-id= : Specific reff_id to fix}';

    protected $description = 'Fix photo status consistency across all modules';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificReffId = $this->option('reff-id');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔧 Checking photo status consistency...');

        // Get all reff_ids with inconsistent photo status
        $query = PhotoApproval::selectRaw('reff_id_pelanggan, module_name, 
                                          COUNT(*) as total_photos,
                                          COUNT(CASE WHEN tracer_approved_at IS NOT NULL THEN 1 END) as tracer_approved_count,
                                          COUNT(CASE WHEN photo_status = "cgp_pending" THEN 1 END) as cgp_pending_count,
                                          COUNT(CASE WHEN photo_status = "draft" AND tracer_approved_at IS NOT NULL THEN 1 END) as inconsistent_count')
            ->groupBy('reff_id_pelanggan', 'module_name')
            ->having('inconsistent_count', '>', 0);

        if ($specificReffId) {
            $query->where('reff_id_pelanggan', $specificReffId);
        }

        $inconsistencies = $query->get();

        if ($inconsistencies->isEmpty()) {
            $this->info('✅ No photo status inconsistencies found!');
            return 0;
        }

        $this->warn('⚠️  Found ' . count($inconsistencies) . ' inconsistent groups:');
        
        $totalFixed = 0;

        foreach ($inconsistencies as $inconsistency) {
            $reffId = $inconsistency->reff_id_pelanggan;
            $module = $inconsistency->module_name;
            
            $this->line("\n📋 {$module} - reff_id: {$reffId}");
            $this->line("   Total photos: {$inconsistency->total_photos}");
            $this->line("   Tracer approved: {$inconsistency->tracer_approved_count}");
            $this->line("   CGP pending: {$inconsistency->cgp_pending_count}");
            $this->line("   Inconsistent: {$inconsistency->inconsistent_count}");

            if (!$dryRun) {
                // Fix inconsistent photos
                $fixed = PhotoApproval::where('reff_id_pelanggan', $reffId)
                    ->where('module_name', $module)
                    ->where('photo_status', 'draft')
                    ->whereNotNull('tracer_approved_at')
                    ->update(['photo_status' => 'cgp_pending']);

                if ($fixed > 0) {
                    $this->info("   ✅ Fixed {$fixed} photos");
                    $totalFixed += $fixed;
                }
            }
        }

        if (!$dryRun) {
            $this->info("\n✅ Total photos fixed: {$totalFixed}");
            
            // Also check module status consistency
            $this->fixModuleStatusConsistency($specificReffId);
        } else {
            $this->info("\n💡 Run without --dry-run to apply fixes");
        }

        return 0;
    }

    private function fixModuleStatusConsistency(?string $specificReffId = null): void
    {
        $this->line("\n🔄 Checking module status consistency...");

        $modules = [
            'sk_data' => SkData::class,
            'sr_data' => SrData::class, 
            'gas_in_data' => GasInData::class
        ];

        $totalModuleFixed = 0;

        foreach ($modules as $table => $model) {
            $moduleQuery = $model::whereNotNull('tracer_approved_at')
                ->where('status', '!=', 'tracer_approved')
                ->whereNull('cgp_approved_at');

            if ($specificReffId) {
                $moduleQuery->where('reff_id_pelanggan', $specificReffId);
            }

            $inconsistentModules = $moduleQuery->get();

            foreach ($inconsistentModules as $moduleData) {
                $this->line("   🔧 Fixing {$table} - reff_id: {$moduleData->reff_id_pelanggan}");
                
                $moduleData->update([
                    'status' => 'tracer_approved',
                    'module_status' => 'cgp_review'
                ]);
                
                $totalModuleFixed++;
            }
        }

        if ($totalModuleFixed > 0) {
            $this->info("   ✅ Fixed {$totalModuleFixed} module status inconsistencies");
        } else {
            $this->info("   ✅ All module statuses are consistent");
        }
    }
}