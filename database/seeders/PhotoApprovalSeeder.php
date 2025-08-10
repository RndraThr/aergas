<?php

namespace Database\Seeders;

use App\Models\PhotoApproval;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PhotoApprovalSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();

        $photoApprovals = [
            // SK Data AER001 - Pending Tracer Review
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_berita_acara_url',
                'photo_url' => '/storage/sk/AER001/foto_berita_acara.jpg',
                'ai_confidence_score' => 89.5,
                'ai_validation_result' => [
                    'detected_objects' => ['document', 'signature'],
                    'confidence' => 89.5,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(8),
                'photo_status' => 'tracer_pending',
            ],
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_pneumatic_sk_url',
                'photo_url' => '/storage/sk/AER001/foto_pneumatic_sk.jpg',
                'ai_confidence_score' => 92.3,
                'ai_validation_result' => [
                    'detected_objects' => ['pneumatic_tool', 'pipe'],
                    'confidence' => 92.3,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(8),
                'photo_status' => 'tracer_pending',
            ],
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_valve_krunchis_url',
                'photo_url' => '/storage/sk/AER001/foto_valve_krunchis.jpg',
                'ai_confidence_score' => 87.8,
                'ai_validation_result' => [
                    'detected_objects' => ['valve', 'gas_connection'],
                    'confidence' => 87.8,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(8),
                'photo_status' => 'tracer_pending',
            ],
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_isometrik_sk_url',
                'photo_url' => '/storage/sk/AER001/foto_isometrik_sk.jpg',
                'ai_confidence_score' => 91.2,
                'ai_validation_result' => [
                    'detected_objects' => ['technical_drawing', 'measurements'],
                    'confidence' => 91.2,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(8),
                'photo_status' => 'tracer_pending',
            ],

            // SK Data AER002 - Completed (CGP Approved)
            [
                'reff_id_pelanggan' => 'AER002',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_berita_acara_url',
                'photo_url' => '/storage/sk/AER002/foto_berita_acara.jpg',
                'ai_confidence_score' => 94.1,
                'ai_validation_result' => [
                    'detected_objects' => ['document', 'signature', 'stamp'],
                    'confidence' => 94.1,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(7),
                'tracer_user_id' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(6),
                'tracer_notes' => 'Foto dokumen jelas dan lengkap',
                'cgp_user_id' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(5),
                'cgp_notes' => 'Approved - sesuai standar',
                'photo_status' => 'cgp_approved',
            ],
            [
                'reff_id_pelanggan' => 'AER002',
                'module_name' => 'sk',
                'photo_field_name' => 'foto_pneumatic_sk_url',
                'photo_url' => '/storage/sk/AER002/foto_pneumatic_sk.jpg',
                'ai_confidence_score' => 88.9,
                'ai_validation_result' => [
                    'detected_objects' => ['pneumatic_tool', 'pipe', 'worker'],
                    'confidence' => 88.9,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(7),
                'tracer_user_id' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(6),
                'tracer_notes' => 'Proses pneumatic terlihat professional',
                'cgp_user_id' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(5),
                'cgp_notes' => 'Good quality work',
                'photo_status' => 'cgp_approved',
            ],

            // SR Data AER002 - Completed
            [
                'reff_id_pelanggan' => 'AER002',
                'module_name' => 'sr',
                'photo_field_name' => 'foto_pneumatic_start_sr_url',
                'photo_url' => '/storage/sr/AER002/foto_pneumatic_start.jpg',
                'ai_confidence_score' => 90.7,
                'ai_validation_result' => [
                    'detected_objects' => ['pneumatic_tool', 'pipe', 'start_point'],
                    'confidence' => 90.7,
                    'validation_passed' => true
                ],
                'ai_approved_at' => Carbon::now()->subDays(6),
                'tracer_user_id' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(5),
                'tracer_notes' => 'Starting point SR terlihat baik',
                'cgp_user_id' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(4),
                'cgp_notes' => 'Perfect execution',
                'photo_status' => 'cgp_approved',
            ],

            // Photo with rejection example
            [
                'reff_id_pelanggan' => 'AER005',
                'module_name' => 'mgrt',
                'photo_field_name' => 'foto_mgrt_url',
                'photo_url' => '/storage/mgrt/AER005/foto_mgrt_rejected.jpg',
                'ai_confidence_score' => 45.2,
                'ai_validation_result' => [
                    'detected_objects' => ['unclear_object'],
                    'confidence' => 45.2,
                    'validation_passed' => false,
                    'rejection_reason' => 'Image too blurry, MGRT not clearly visible'
                ],
                'ai_approved_at' => null,
                'photo_status' => 'ai_rejected',
                'rejection_reason' => 'AI Validation Failed: Image quality too low, MGRT device not clearly identifiable',
            ]
        ];

        foreach ($photoApprovals as $data) {
            PhotoApproval::create($data);
        }

        $this->command->info('Created ' . count($photoApprovals) . ' photo approval records successfully.');
    }
}
