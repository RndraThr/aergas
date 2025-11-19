<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('goods_receipt_details', function (Blueprint $table) {
            // Rename existing quantity to received_quantity
            $table->renameColumn('quantity', 'received_quantity');

            // Add ordered_quantity field
            $table->decimal('ordered_quantity', 12, 2)->nullable()->after('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_details', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('received_quantity', 'quantity');

            // Drop ordered_quantity
            $table->dropColumn('ordered_quantity');
        });
    }
};
