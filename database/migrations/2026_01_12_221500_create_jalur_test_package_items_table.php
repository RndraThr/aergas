<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('jalur_test_package_items');
        Schema::create('jalur_test_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_package_id')->constrained('jalur_test_packages')->onDelete('cascade');
            $table->foreignId('line_number_id')->constrained('jalur_line_numbers');

            // Opsional: Snapshot status saat item ditambahkan
            $table->timestamps();

            // Prevent duplicate line numbers in the same package (optional, but good practice)
            $table->unique(['test_package_id', 'line_number_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('jalur_test_package_items');
    }
};
