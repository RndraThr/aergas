<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToMultiRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:migrate-to-multi
                           {--force : Force migration even if user_roles already exist}
                           {--user= : Migrate specific user ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from single role system to multi-role system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration to multi-role system...');

        if (!$this->option('force') && UserRole::count() > 0) {
            $this->warn('Multi-role data already exists. Use --force to override.');
            if (!$this->confirm('Continue anyway?')) {
                return 0;
            }
        }

        $query = User::whereNotNull('role');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('No users found to migrate.');
            return 0;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                DB::beginTransaction();

                // Check if user already has multi-roles
                if (!$this->option('force') && $user->userRoles()->exists()) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Create UserRole record for existing single role
                UserRole::updateOrCreate([
                    'user_id' => $user->id,
                    'role' => $user->role,
                ], [
                    'is_active' => true,
                    'assigned_at' => $user->created_at ?? now(),
                    'assigned_by' => null, // System migration
                ]);

                DB::commit();
                $migrated++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to migrate user {$user->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration completed:");
        $this->line("✅ Migrated: {$migrated}");
        $this->line("⏭️ Skipped: {$skipped}");
        $this->line("❌ Errors: {$errors}");

        if ($migrated > 0) {
            $this->info("\nMulti-role system is now active!");
            $this->line("Users can now have multiple roles assigned.");
            $this->line("Use: php artisan role:assign {user_id} {role} to assign additional roles");
        }

        return 0;
    }
}