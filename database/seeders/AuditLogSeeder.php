<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AuditLogSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();
        $skUser = User::where('role', 'sk')->first();

        $auditLogs = [
            [
                'user_id' => $skUser->id,
                'action' => 'create',
                'model_type' => 'SkData',
                'model_id' => 1,
                'reff_id_pelanggan' => 'AER001',
                'old_values' => null,
                'new_values' => [
                    'reff_id_pelanggan' => 'AER001',
                    // 'nama_petugas_sk' => 'Petugas Sambungan Kompor 1',
                    'module_status' => 'draft'
                ],
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'description' => 'Created new SK Data record',
                'created_at' => Carbon::now()->subDays(9),
            ],
            [
                'user_id' => $skUser->id,
                'action' => 'update',
                'model_type' => 'SkData',
                'model_id' => 1,
                'reff_id_pelanggan' => 'AER001',
                'old_values' => [
                    'foto_berita_acara_url' => null
                ],
                'new_values' => [
                    'foto_berita_acara_url' => '/storage/sk/AER001/foto_berita_acara.jpg'
                ],
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'description' => 'Uploaded foto berita acara',
                'created_at' => Carbon::now()->subDays(8),
            ],
            [
                'user_id' => $tracer->id,
                'action' => 'approve',
                'model_type' => 'PhotoApproval',
                'model_id' => 5,
                'reff_id_pelanggan' => 'AER002',
                'old_values' => [
                    'photo_status' => 'tracer_pending'
                ],
                'new_values' => [
                    'photo_status' => 'tracer_approved',
                    'tracer_approved_at' => Carbon::now()->subDays(6)->toDateTimeString()
                ],
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0',
                'description' => 'Tracer approved photo: foto_berita_acara_url',
                'created_at' => Carbon::now()->subDays(6),
            ],
            [
                'user_id' => $admin->id,
                'action' => 'approve',
                'model_type' => 'PhotoApproval',
                'model_id' => 5,
                'reff_id_pelanggan' => 'AER002',
                'old_values' => [
                    'photo_status' => 'cgp_pending'
                ],
                'new_values' => [
                    'photo_status' => 'cgp_approved',
                    'cgp_approved_at' => Carbon::now()->subDays(5)->toDateTimeString()
                ],
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'description' => 'CGP final approval for photo: foto_berita_acara_url',
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'user_id' => $admin->id,
                'action' => 'update',
                'model_type' => 'CalonPelanggan',
                'model_id' => null,
                'reff_id_pelanggan' => 'AER002',
                'old_values' => [
                    'progress_status' => 'sk'
                ],
                'new_values' => [
                    'progress_status' => 'sr'
                ],
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'description' => 'Updated customer progress status after SK completion',
                'created_at' => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($auditLogs as $data) {
            AuditLog::create($data);
        }

        $this->command->info('Created ' . count($auditLogs) . ' audit log records successfully.');
    }
}
