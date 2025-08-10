<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();
        $skUser = User::where('role', 'sk')->first();

        $notifications = [
            // Notifications for Tracer
            [
                'user_id' => $tracer->id,
                'type' => 'photo_pending',
                'title' => 'Photos Pending Your Review',
                'message' => '4 photos from customer AER001 are waiting for your approval',
                'data' => [
                    'reff_id_pelanggan' => 'AER001',
                    'module' => 'sk',
                    'photo_count' => 4,
                    'url' => '/tracer/photo-review/AER001'
                ],
                'is_read' => false,
                'priority' => 'high',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'user_id' => $tracer->id,
                'type' => 'sla_warning',
                'title' => 'SLA Warning - Photo Review',
                'message' => 'Photos from AER001 have been pending for more than 20 hours',
                'data' => [
                    'reff_id_pelanggan' => 'AER001',
                    'pending_hours' => 20,
                    'sla_limit' => 24
                ],
                'is_read' => false,
                'priority' => 'urgent',
                'created_at' => Carbon::now()->subHours(1),
            ],

            // Notifications for Admin (CGP)
            [
                'user_id' => $admin->id,
                'type' => 'cgp_review_pending',
                'title' => 'CGP Review Required',
                'message' => 'New photos approved by Tracer are ready for CGP review',
                'data' => [
                    'pending_count' => 3,
                    'modules' => ['sk', 'sr'],
                    'url' => '/admin/cgp-review'
                ],
                'is_read' => true,
                'read_at' => Carbon::now()->subMinutes(30),
                'priority' => 'medium',
                'created_at' => Carbon::now()->subHours(6),
            ],

            // Notifications for SK User
            [
                'user_id' => $skUser->id,
                'type' => 'photo_rejected',
                'title' => 'Photo Rejected - Action Required',
                'message' => 'Your photo submission for AER005 has been rejected by AI validation',
                'data' => [
                    'reff_id_pelanggan' => 'AER005',
                    'module' => 'mgrt',
                    'rejected_photo' => 'foto_mgrt_url',
                    'rejection_reason' => 'Image quality too low',
                    'url' => '/sk/resubmit/AER005'
                ],
                'is_read' => false,
                'priority' => 'high',
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => $skUser->id,
                'type' => 'module_completed',
                'title' => 'Module Completion Confirmed',
                'message' => 'Your SK work for customer AER002 has been fully approved',
                'data' => [
                    'reff_id_pelanggan' => 'AER002',
                    'module' => 'sk',
                    'completion_date' => Carbon::now()->subDays(5)->toDateString()
                ],
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(4),
                'priority' => 'low',
                'created_at' => Carbon::now()->subDays(5),
            ],

            // System notifications
            [
                'user_id' => $admin->id,
                'type' => 'system_alert',
                'title' => 'Daily Summary Report',
                'message' => 'Daily operations summary is ready for review',
                'data' => [
                    'date' => Carbon::now()->subDay()->toDateString(),
                    'completed_modules' => 3,
                    'pending_approvals' => 5,
                    'new_registrations' => 2
                ],
                'is_read' => false,
                'priority' => 'medium',
                'created_at' => Carbon::now()->subHours(8),
            ]
        ];

        foreach ($notifications as $data) {
            Notification::create($data);
        }

        $this->command->info('Created ' . count($notifications) . ' notification records successfully.');
    }
}
