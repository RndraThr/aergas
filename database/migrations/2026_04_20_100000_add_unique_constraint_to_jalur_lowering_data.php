<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('jalur_lowering_data')
            ->selectRaw('line_number_id, tanggal_jalur, tipe_bongkaran, penggelaran, bongkaran, kedalaman_lowering, COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupBy('line_number_id', 'tanggal_jalur', 'tipe_bongkaran', 'penggelaran', 'bongkaran', 'kedalaman_lowering')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $samples = $duplicates->take(5)->map(function ($d) {
                return "line={$d->line_number_id}, tgl={$d->tanggal_jalur}, tipe={$d->tipe_bongkaran}, "
                    . "lowering={$d->penggelaran}, bongkaran={$d->bongkaran}, kedalaman={$d->kedalaman_lowering} ({$d->total}x)";
            })->implode(' | ');

            throw new \Exception(
                "Terdapat " . $duplicates->count() . " kombinasi duplikat di jalur_lowering_data. "
                . "Bersihkan data duplikat terlebih dahulu sebelum migrate. Contoh: " . $samples
            );
        }

        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->unique(
                ['line_number_id', 'tanggal_jalur', 'tipe_bongkaran', 'penggelaran', 'bongkaran', 'kedalaman_lowering', 'deleted_at'],
                'jalur_lowering_data_composite_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropUnique('jalur_lowering_data_composite_unique');
        });
    }
};
