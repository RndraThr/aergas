<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $t) {
            $t->engine = 'InnoDB';

            $t->id();

            // Relasi polymorphic ke SK/SR
            $t->string('module_type');     // App\Models\SkData / App\Models\SrData
            $t->unsignedBigInteger('module_id');

            // Reff calon pelanggan (string PK)
            $t->string('reff_id_pelanggan', 50);
            $t->foreign('reff_id_pelanggan')
                ->references('reff_id_pelanggan')->on('calon_pelanggan')
                ->cascadeOnDelete();

            // Nomor permintaan (opsional, bisa diisi saat approved/issued)
            $t->string('request_no')->nullable()->unique();

            $t->enum('status', [
                'draft', 'submitted', 'approved', 'issued', 'closed', 'canceled'
            ])->default('draft')->index();

            $t->text('notes')->nullable();

            // Audit
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $t->timestamps();
            $t->softDeletes();

            $t->index(['module_type','module_id'], 'idx_mr_module');
        });

        Schema::create('material_request_items', function (Blueprint $t) {
            $t->engine = 'InnoDB';

            $t->id();
            $t->foreignId('material_request_id')
              ->constrained('material_requests')
              ->cascadeOnDelete();

            $t->foreignId('gudang_item_id')
              ->constrained('gudang_items')
              ->restrictOnDelete(); // harus exist di master gudang

            $t->string('unit', 32)->nullable(); // default ikut master, tapi boleh override

            // Kuantitas
            $t->decimal('qty_requested', 18, 3);
            $t->decimal('qty_approved',  18, 3)->default(0);
            $t->decimal('qty_issued',    18, 3)->default(0);
            $t->decimal('qty_installed', 18, 3)->default(0);
            $t->decimal('qty_returned',  18, 3)->default(0);
            $t->decimal('qty_reject',    18, 3)->default(0);

            $t->text('notes')->nullable();

            $t->timestamps();

            // Satu item gudang hanya sekali per request
            $t->unique(['material_request_id','gudang_item_id'], 'uq_mr_item');
            $t->index(['gudang_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
    }
};
