<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            // Add line_number_id to reference specific line number
            $table->unsignedBigInteger('line_number_id')->nullable()->after('fitting_type_id');
            
            // Add physical dimension fields from the form
            $table->decimal('panjang_fitting', 8, 2)->nullable()->after('tipe_penyambungan');
            $table->decimal('lebar_fitting', 8, 2)->nullable()->after('panjang_fitting');
            $table->decimal('kedalaman_fitting', 8, 2)->nullable()->after('lebar_fitting');
            $table->string('lokasi_joint')->nullable()->after('kedalaman_fitting');
            
            // Add foreign key constraint
            $table->foreign('line_number_id')->references('id')->on('jalur_line_numbers')->nullOnDelete();
            $table->index('line_number_id');
        });
    }

    public function down(): void
    {
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropForeign(['line_number_id']);
            $table->dropColumn([
                'line_number_id',
                'panjang_fitting',
                'lebar_fitting', 
                'kedalaman_fitting',
                'lokasi_joint'
            ]);
        });
    }
};