<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sr_data', function (Blueprint $t) {
            if (!Schema::hasColumn('sr_data','jenis_tapping')) {
                $t->string('jenis_tapping', 20)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('sr_data','panjang_pipa_pe_m')) {
                $t->decimal('panjang_pipa_pe_m', 10, 2)->nullable()->after('jenis_tapping');
            }
            if (!Schema::hasColumn('sr_data','panjang_casing_crossing_m')) {
                $t->decimal('panjang_casing_crossing_m', 10, 2)->nullable()->after('panjang_pipa_pe_m');
            }
            // Kolom lama (kompatibilitas) – kalau belum ada tapi kamu ingin tetap simpan
            if (!Schema::hasColumn('sr_data','panjang_pipa_pe')) {
                $t->decimal('panjang_pipa_pe', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('sr_data','panjang_casing_crossing_sr')) {
                $t->decimal('panjang_casing_crossing_sr', 10, 2)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('sr_data', function (Blueprint $t) {
            // optional: drop kolom baru
            // $t->dropColumn([...]);
        });
    }
};
