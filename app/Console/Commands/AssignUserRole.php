<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:assign
                           {user : User ID or username}
                           {role : Role to assign}
                           {--remove : Remove role instead of assigning}
                           {--list : List user\'s current roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign or remove roles from users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIdentifier = $this->argument('user');
        $role = $this->argument('role');

        // Find user by ID or username
        $user = User::where('id', $userIdentifier)
                   ->orWhere('username', $userIdentifier)
                   ->first();

        if (!$user) {
            $this->error("User '{$userIdentifier}' not found.");
            return 1;
        }

        // List roles option
        if ($this->option('list')) {
            $this->showUserRoles($user);
            return 0;
        }

        // Validate role
        if (!in_array($role, UserRole::AVAILABLE_ROLES)) {
            $this->error("Invalid role '{$role}'. Available roles: " . implode(', ', UserRole::AVAILABLE_ROLES));
            return 1;
        }

        // Remove role
        if ($this->option('remove')) {
            return $this->removeRole($user, $role);
        }

        // Assign role
        return $this->assignRole($user, $role);
    }

    private function assignRole(User $user, string $role): int
    {
        try {
            $userRole = $user->assignRole($role);

            if ($userRole->wasRecentlyCreated) {
                $this->info("✅ Role '{$role}' assigned to user '{$user->username}' successfully.");
            } else {
                $this->warn("⚠️ User '{$user->username}' already has role '{$role}'.");
            }

            $this->showUserRoles($user);
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to assign role: " . $e->getMessage());
            return 1;
        }
    }

    private function removeRole(User $user, string $role): int
    {
        try {
            $removed = $user->removeRole($role);

            if ($removed) {
                $this->info("✅ Role '{$role}' removed from user '{$user->username}' successfully.");
            } else {
                $this->warn("⚠️ User '{$user->username}' does not have role '{$role}'.");
            }

            $this->showUserRoles($user);
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to remove role: " . $e->getMessage());
            return 1;
        }
    }

    private function showUserRoles(User $user): void
    {
        $roles = $user->getAllActiveRoles();

        $this->newLine();
        $this->line("👤 User: {$user->username} ({$user->full_name})");
        $this->line("🔑 Active Roles: " . (empty($roles) ? 'None' : implode(', ', $roles)));

        if ($user->role) {
            $this->line("📝 Legacy Role: {$user->role}");
        }
    }
}