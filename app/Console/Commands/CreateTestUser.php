<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test';
    protected $description = 'Create test user for development';

    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@test.com'],
            [
                'name' => 'Test User',
                'full_name' => 'Test User Development',
                'username' => 'testuser',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true
            ]
        );

        $this->info('Test user created/found: ' . $user->email);
        $this->info('Password: password');
        $this->info('Role: ' . $user->role);

        return 0;
    }
}