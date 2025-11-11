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
        Schema::create('hse_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan'); // SERT Leader, P3K, dll
            $table->string('nama_petugas');
            $table->string('nomor_telepon', 20);
            $table->string('kategori')->default('emergency_response'); // emergency_response, medical, security
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hse_emergency_contacts');
    }
};
