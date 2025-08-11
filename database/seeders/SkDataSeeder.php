<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CalonPelanggan;
use App\Models\SkData;

class SkDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Seeder Admin',
            'email' => 'seeder-admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $pelanggan = CalonPelanggan::take(6)->get();
        if ($pelanggan->isEmpty()) {
            $this->command?->warn('Skip SkDataSeeder: tabel calon_pelanggan masih kosong.');
            return;
        }

        $rows = [
            // 1) Draft (AI belum semua pass)
            [
                'status'             => SkData::STATUS_DRAFT,
                'ai_overall_status'  => 'pending',
                'ai_checked_at'      => null,
                'tanggal_instalasi'  => null,
                'nomor_sk'           => null,
            ],
            // 2) Semua foto pass → ready_for_tracer
            [
                'status'             => SkData::STATUS_READY_FOR_TRACER,
                'ai_overall_status'  => 'passed',
                'ai_checked_at'      => now(),
                'tanggal_instalasi'  => null,
                'nomor_sk'           => null,
            ],
            // 3) Tracer approved
            [
                'status'              => SkData::STATUS_TRACER_APPROVED,
                'ai_overall_status'   => 'passed',
                'ai_checked_at'       => now(),
                'tracer_approved_at'  => now(),
                'tracer_approved_by'  => $user->id,
                'tanggal_instalasi'   => null,
                'nomor_sk'            => null,
            ],
            // 4) CGP approved
            [
                'status'              => SkData::STATUS_CGP_APPROVED,
                'ai_overall_status'   => 'passed',
                'ai_checked_at'       => now(),
                'tracer_approved_at'  => now()->subDay(),
                'tracer_approved_by'  => $user->id,
                'cgp_approved_at'     => now(),
                'cgp_approved_by'     => $user->id,
                'tanggal_instalasi'   => null,
                'nomor_sk'            => null,
            ],
            // 5) Scheduled (punya nomor SK & tanggal)
            [
                'status'              => SkData::STATUS_SCHEDULED,
                'ai_overall_status'   => 'passed',
                'ai_checked_at'       => now(),
                'tracer_approved_at'  => now()->subDays(3),
                'tracer_approved_by'  => $user->id,
                'cgp_approved_at'     => now()->subDays(2),
                'cgp_approved_by'     => $user->id,
                'tanggal_instalasi'   => Carbon::now()->addDays(5),
                'nomor_sk'            => self::makeNomor('SK'),
            ],
            // 6) Completed (tanggal terlewat)
            [
                'status'              => SkData::STATUS_COMPLETED,
                'ai_overall_status'   => 'passed',
                'ai_checked_at'       => now(),
                'tracer_approved_at'  => now()->subDays(10),
                'tracer_approved_by'  => $user->id,
                'cgp_approved_at'     => now()->subDays(9),
                'cgp_approved_by'     => $user->id,
                'tanggal_instalasi'   => Carbon::now()->subDays(2),
                'nomor_sk'            => self::makeNomor('SK'),
            ],
        ];

        $i = 0;
        foreach ($rows as $data) {
            $cp = $pelanggan[$i % $pelanggan->count()];
            SkData::create(array_merge($data, [
                'reff_id_pelanggan' => $cp->reff_id_pelanggan, // <- kunci baru
                'notes'             => $data['status'] === SkData::STATUS_DRAFT
                                        ? 'Contoh data draft (AI belum semua pass)' : null,
                'created_by'        => $user->id,
                'updated_by'        => $user->id,
            ]));
            $i++;
        }
    }

    private static function makeNomor(string $prefix): string
    {
        return sprintf('%s-%s-%04d', strtoupper($prefix), now()->format('Ym'), random_int(1, 9999));
    }
}
