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
        Schema::create('hse_tbm_materi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toolbox_meeting_id');
            $table->integer('urutan')->default(1);
            $table->text('materi_pembahasan');
            $table->timestamps();

            $table->foreign('toolbox_meeting_id')->references('id')->on('hse_toolbox_meetings')->onDelete('cascade');
            $table->index('toolbox_meeting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hse_tbm_materi');
    }
};
