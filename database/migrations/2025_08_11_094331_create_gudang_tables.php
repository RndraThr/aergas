<?php

// database/migrations/2025_08_11_000001_create_gudang_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // gudang_items
        if (!Schema::hasTable('gudang_items')) {
            Schema::create('gudang_items', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('unit')->nullable();                  // Buah/meter/roll/dll
                $t->enum('category', ['SR_FIM','SK_FIM','KSM']);  // Kategori dari sheet
                $t->boolean('is_active')->default(true);
                $t->json('meta')->nullable();                    // cadangan info
                $t->timestamps();

                $t->index(['category','is_active'], 'idx_items_cat_active');
            });
        }

        // gudang_transactions
        if (!Schema::hasTable('gudang_transactions')) {
            Schema::create('gudang_transactions', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->foreignId('gudang_item_id')->constrained('gudang_items')->cascadeOnDelete();
                $t->enum('type', ['IN','OUT','RETURN','REJECT','INSTALLED','ADJUST']); // Masuk/Keluar/Kembali/Reject/Terpasang/Penyesuaian
                $t->decimal('qty', 18, 3);
                $t->string('unit')->nullable();
                $t->string('ref_no')->nullable();               // no dok/SPK/BA
                $t->nullableMorphs('sourceable');               // link ke SK/SR/GasIn nanti
                $t->text('notes')->nullable();
                $t->timestamp('transacted_at')->useCurrent();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();

                // index kinerja query
                $t->index(['gudang_item_id','type'], 'idx_tx_item_type');
                $t->index('transacted_at', 'idx_tx_date');
                $t->index(['sourceable_type','sourceable_id'], 'idx_tx_sourceable');
            });
        }

        // VIEW stok on-hand (idempotent)
        DB::statement('DROP VIEW IF EXISTS gudang_stock_balances');

        DB::statement("
            CREATE VIEW gudang_stock_balances AS
            SELECT
              gi.id AS gudang_item_id,
              COALESCE(SUM(CASE WHEN gt.type IN ('IN','RETURN','ADJUST') THEN gt.qty ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN gt.type IN ('OUT','REJECT','INSTALLED') THEN gt.qty ELSE 0 END), 0) AS on_hand
            FROM gudang_items gi
            LEFT JOIN gudang_transactions gt ON gt.gudang_item_id = gi.id
            GROUP BY gi.id
        ");
    }

    public function down(): void
    {
        // Drop view dulu supaya tidak menghalangi drop table
        DB::statement('DROP VIEW IF EXISTS gudang_stock_balances');
        Schema::dropIfExists('gudang_transactions');
        Schema::dropIfExists('gudang_items');
    }
};
