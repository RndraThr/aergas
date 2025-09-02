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
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            // Add validation audit fields
            $table->timestamp('validated_at')->nullable()->after('tanggal_registrasi');
            $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
            $table->text('validation_notes')->nullable()->after('validated_by');
            
            // Add foreign key to users table
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
        
        // IMPORTANT: Auto-validate existing customers with SK/SR/Gas In data
        // This ensures backward compatibility for production data
        $existingCustomersWithData = DB::table('calon_pelanggan')
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('sk_data')
                        ->whereColumn('sk_data.reff_id_pelanggan', 'calon_pelanggan.reff_id_pelanggan');
                })
                ->orWhereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('sr_data')
                        ->whereColumn('sr_data.reff_id_pelanggan', 'calon_pelanggan.reff_id_pelanggan');
                })
                ->orWhereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('gas_in_data')
                        ->whereColumn('gas_in_data.reff_id_pelanggan', 'calon_pelanggan.reff_id_pelanggan');
                });
            });
            
        // Update these customers to validated status
        $existingCustomersWithData->update([
            'status' => 'lanjut',
            'validated_at' => now(),
            'validation_notes' => 'Auto-validated during system upgrade (existing production data)'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_at', 'validated_by', 'validation_notes']);
        });
    }
};
