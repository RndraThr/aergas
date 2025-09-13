<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'jalur' to the existing role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'sk', 'sr', 'mgrt', 'gas_in', 'pic', 'tracer', 'jalur') DEFAULT 'sk'");
    }

    public function down(): void
    {
        // Remove 'jalur' from role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'sk', 'sr', 'mgrt', 'gas_in', 'pic', 'tracer') DEFAULT 'sk'");
    }
};