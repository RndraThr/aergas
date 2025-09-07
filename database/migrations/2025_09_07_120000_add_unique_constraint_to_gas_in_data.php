<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, identify and handle duplicate records
        $this->handleDuplicates();
        
        // Then add unique constraint
        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->unique('reff_id_pelanggan', 'unique_gasin_reff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->dropUnique('unique_gasin_reff_id');
        });
    }

    /**
     * Handle duplicate records before adding unique constraint
     */
    private function handleDuplicates(): void
    {
        // Find duplicate reff_id_pelanggan
        $duplicates = DB::select("
            SELECT reff_id_pelanggan, COUNT(*) as count 
            FROM gas_in_data 
            WHERE deleted_at IS NULL 
            GROUP BY reff_id_pelanggan 
            HAVING count > 1
        ");

        foreach ($duplicates as $duplicate) {
            $reffId = $duplicate->reff_id_pelanggan;
            
            // Get all records for this reff_id, ordered by creation date (keep the oldest)
            $records = DB::table('gas_in_data')
                ->where('reff_id_pelanggan', $reffId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($records->count() > 1) {
                // Keep the first (oldest) record, soft delete the rest
                $recordsToDelete = $records->skip(1);
                
                foreach ($recordsToDelete as $record) {
                    DB::table('gas_in_data')
                        ->where('id', $record->id)
                        ->update(['deleted_at' => now()]);
                        
                    // Also soft delete related photo_approvals
                    DB::table('photo_approvals')
                        ->where('reff_id_pelanggan', $reffId)
                        ->where('module_name', 'gas_in')
                        ->whereNull('deleted_at')
                        ->where('created_at', '>=', $record->created_at)
                        ->update(['deleted_at' => now()]);
                }
                
                echo "✅ Cleaned up duplicates for reff_id: {$reffId}\n";
            }
        }
    }
};