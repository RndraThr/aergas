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
                'password' => Hash::make('Rendra123'),
                'name' => 'Rendra',
                'full_name' => 'Rendra Tuharea',
                'role' => 'super_admin',
                'is_active' => true,
            ],
            [
                'username' => 'admin',
                'email' => 'admin@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Admin CGP',
                'full_name' => 'Administrator CGP Reviewer',
                'role' => 'admin',
                'is_active' => true,
            ],
            [
                'username' => 'tracer001',
                'email' => 'tracer@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Tracer Field',
                'full_name' => 'Tracer Field Inspector',
                'role' => 'tracer',
                'is_active' => true,
            ],
            [
                'username' => 'sk001',
                'email' => 'sk001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SK 1',
                'full_name' => 'Petugas Sambungan Kompor 1',
                'role' => 'sk',
                'is_active' => true,
            ],
            [
                'username' => 'sk002',
                'email' => 'sk002@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SK 2',
                'full_name' => 'Petugas Sambungan Kompor 2',
                'role' => 'sk',
                'is_active' => true,
            ],
            [
                'username' => 'sr001',
                'email' => 'sr001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SR 1',
                'full_name' => 'Petugas Sambungan Rumah 1',
                'role' => 'sr',
                'is_active' => true,
            ],
            [
                'username' => 'sr002',
                'email' => 'sr002@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas SR 2',
                'full_name' => 'Petugas Sambungan Rumah 2',
                'role' => 'sr',
                'is_active' => true,
            ],
            [
                'username' => 'mgrt001',
                'email' => 'mgrt001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas MGRT 1',
                'full_name' => 'Petugas MGRT dan Pondasi 1',
                'role' => 'mgrt',
                'is_active' => true,
            ],
            [
                'username' => 'gasin001',
                'email' => 'gasin001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'Petugas Gas In 1',
                'full_name' => 'Petugas Gas In 1',
                'role' => 'gas_in',
                'is_active' => true,
            ],
            [
                'username' => 'pic001',
                'email' => 'pic001@aergas.com',
                'password' => Hash::make('password'),
                'name' => 'PIC Pipa 1',
                'full_name' => 'PIC Jalur Pipa dan Penyambungan 1',
                'role' => 'pic',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Created ' . count($users) . ' users successfully.');
    }
}
