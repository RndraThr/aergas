<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing single roles to new user_roles table
        $users = DB::table('users')->whereNotNull('role')->get();

        foreach ($users as $user) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role' => $user->role,
                'is_active' => true,
                'assigned_at' => $user->created_at ?? now(),
                'assigned_by' => null, // System migration
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all records from user_roles (data will be lost)
        DB::table('user_roles')->delete();
    }
};
