<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Starting AERGAS Database Seeding...');

        $this->call([
            UserSeeder::class,
            // CalonPelangganSeeder::class,
            // SkDataSeeder::class,
            // SrDataSeeder::class,
            // MgrtDataSeeder::class,
            // GasInDataSeeder::class,
            // PhotoApprovalSeeder::class,
            // FileStorageSeeder::class,
            // NotificationSeeder::class,
            // AuditLogSeeder::class,
        ]);

        $this->command->info('✅ AERGAS Database Seeding completed successfully!');

        // Display login information
        $this->command->info('');
        $this->command->info('📋 Default Login Credentials:');
        $this->command->info('Super Admin: superadmin@aergas.com / password');
        $this->command->info('Admin CGP: admin@aergas.com / password');
        $this->command->info('Tracer: tracer@aergas.com / password');
        $this->command->info('SK User: sk001@aergas.com / password');
        $this->command->info('SR User: sr001@aergas.com / password');
        $this->command->info('');
    }
}
