<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('jalur_test_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('jalur_clusters');
            $table->string('test_package_code')->unique(); // e.g., TP-KRG-001

            // Status Pipeline: draft -> flushing -> pneumatic -> purging -> gas_in -> completed
            $table->enum('status', ['draft', 'flushing', 'pneumatic', 'purging', 'gas_in', 'completed'])->default('draft');
            $table->integer('step_order')->default(0); // 0:Draft, 1:Flushing, 2:Pneumatic, 3:Purging, 4:GasIn

            // Step 1: Flushing
            $table->date('flushing_date')->nullable();
            $table->string('flushing_evidence_path')->nullable(); // Foto/PDF
            $table->text('flushing_notes')->nullable();

            // Step 2: Pneumatic Test
            $table->date('pneumatic_date')->nullable();
            $table->string('pneumatic_evidence_path')->nullable();
            $table->text('pneumatic_notes')->nullable();
            // Data teknis pneumatic opsional (bisa ditambah nanti)
            $table->decimal('pneumatic_pressure_start', 8, 2)->nullable();
            $table->decimal('pneumatic_pressure_end', 8, 2)->nullable();

            // Step 3: N2 Purging
            $table->date('purging_date')->nullable();
            $table->string('purging_evidence_path')->nullable();
            $table->text('purging_notes')->nullable();

            // Step 4: Gas In
            $table->date('gas_in_date')->nullable();
            $table->string('gas_in_evidence_path')->nullable();
            $table->text('gas_in_notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jalur_test_packages');
    }
};
