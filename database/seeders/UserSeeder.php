<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'username' => 'Rendra',
                'email' => 'rendrabtuharea@gmail.com',
                'password' => Hash::make('Rennn123!#'),
                'name' => 'Rendra',
                'full_name' => 'Rendra Tuharea',
                'roles' => ['super_admin'], // Changed to array for multi-role
                'is_active' => true,
            ],
            [
                'username' => 'admin',
                'email' => 'admin@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Admin CGP',
                'full_name' => 'Administrator CGP Reviewer',
                'roles' => ['admin'],
                'is_active' => true,
            ],
            [
                'username' => 'tracer001',
                'email' => 'tracer@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Tracer Field',
                'full_name' => 'Tracer Field Inspector',
                'roles' => ['tracer'],
                'is_active' => true,
            ],

            // SK Personnel
            [
                'username' => 'sk001',
                'email' => 'sk001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SK 1',
                'full_name' => 'Petugas Sambungan Kompor 1',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            [
                'username' => 'sk002',
                'email' => 'sk002@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SK 2',
                'full_name' => 'Petugas Sambungan Kompor 2',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            // New SK Personnel
            [
                'username' => 'selamet',
                'email' => 'selamet@aergas.com',
                'password' => Hash::make('aergas_selamet'),
                'name' => 'Selamet',
                'full_name' => 'Selamet',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            [
                'username' => 'jaya',
                'email' => 'jaya@aergas.com',
                'password' => Hash::make('aergas_jaya'),
                'name' => 'Jaya',
                'full_name' => 'Jaya',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            [
                'username' => 'aya',
                'email' => 'aya@aergas.com',
                'password' => Hash::make('aergas_aya'),
                'name' => 'Aya',
                'full_name' => 'Aya',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            [
                'username' => 'sugi',
                'email' => 'sugi@aergas.com',
                'password' => Hash::make('aergas_sugi'),
                'name' => 'Sugi',
                'full_name' => 'Sugi',
                'roles' => ['sk'],
                'is_active' => true,
            ],
            [
                'username' => 'buyung',
                'email' => 'buyung@aergas.com',
                'password' => Hash::make('aergas_buyung'),
                'name' => 'Buyung',
                'full_name' => 'Buyung',
                'roles' => ['sk'],
                'is_active' => true,
            ],

            // SR Personnel
            [
                'username' => 'sr001',
                'email' => 'sr001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SR 1',
                'full_name' => 'Petugas Sambungan Rumah 1',
                'roles' => ['sr'],
                'is_active' => true,
            ],
            [
                'username' => 'sr002',
                'email' => 'sr002@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SR 2',
                'full_name' => 'Petugas Sambungan Rumah 2',
                'roles' => ['sr'],
                'is_active' => true,
            ],
            // New SR Personnel
            [
                'username' => 'lepek',
                'email' => 'lepek@aergas.com',
                'password' => Hash::make('aergas_lepek'),
                'name' => 'Lepek',
                'full_name' => 'Lepek',
                'roles' => ['sr'],
                'is_active' => true,
            ],

            [
                'username' => 'mgrt001',
                'email' => 'mgrt001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas MGRT 1',
                'full_name' => 'Petugas MGRT dan Pondasi 1',
                'roles' => ['mgrt'],
                'is_active' => true,
            ],

            // Gas In Personnel
            [
                'username' => 'gasin001',
                'email' => 'gasin001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas Gas In 1',
                'full_name' => 'Petugas Gas In 1',
                'roles' => ['gas_in'],
                'is_active' => true,
            ],
            // New Gas In Personnel
            [
                'username' => 'jidan',
                'email' => 'jidan@aergas.com',
                'password' => Hash::make('aergas_jidan'),
                'name' => 'Jidan',
                'full_name' => 'Jidan',
                'roles' => ['gas_in'],
                'is_active' => true,
            ],

            [
                'username' => 'cgp001',
                'email' => 'cgp001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'CGP Pipa 1',
                'full_name' => 'CGP Jalur Pipa dan Penyambungan 1',
                'roles' => ['cgp'],
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            // Extract roles before creating user
            $roles = $userData['roles'];
            unset($userData['roles']);

            // Create user without role column
            $user = User::create($userData);

            // Assign roles using the multi-role system
            foreach ($roles as $role) {
                $user->assignRole($role);
            }

            $this->command->info("Created user: {$user->username} with roles: " . implode(', ', $roles));
        }

        $this->command->info('Created ' . count($users) . ' users successfully.');
    }
}
