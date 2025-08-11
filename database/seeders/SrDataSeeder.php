<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CalonPelanggan;
use App\Models\SrData;

class SrDataSeeder extends Seeder
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
            $this->command?->warn('Skip SrDataSeeder: tabel calon_pelanggan masih kosong.');
            return;
        }

        $rows = [
            // 1) Draft
            [
                'status'                        => SrData::STATUS_DRAFT,
                'ai_overall_status'             => 'pending',
                'ai_checked_at'                 => null,
                'tanggal_pemasangan'            => null,
                'panjang_pipa_pe'               => null,
                'panjang_casing_crossing_sr'    => null,
                'nomor_sr'                      => null,
            ],
            // 2) Ready for tracer
            [
                'status'                        => SrData::STATUS_READY_FOR_TRACER,
                'ai_overall_status'             => 'passed',
                'ai_checked_at'                 => now(),
                'tanggal_pemasangan'            => null,
                'panjang_pipa_pe'               => 12.50,
                'panjang_casing_crossing_sr'    => 2.00,
                'nomor_sr'                      => null,
            ],
            // 3) Tracer approved
            [
                'status'                        => SrData::STATUS_TRACER_APPROVED,
                'ai_overall_status'             => 'passed',
                'ai_checked_at'                 => now(),
                'tracer_approved_at'            => now(),
                'tracer_approved_by'            => $user->id,
                'tanggal_pemasangan'            => null,
                'panjang_pipa_pe'               => 15.25,
                'panjang_casing_crossing_sr'    => 3.10,
                'nomor_sr'                      => null,
            ],
            // 4) CGP approved
            [
                'status'                        => SrData::STATUS_CGP_APPROVED,
                'ai_overall_status'             => 'passed',
                'ai_checked_at'                 => now(),
                'tracer_approved_at'            => now()->subDay(),
                'tracer_approved_by'            => $user->id,
                'cgp_approved_at'               => now(),
                'cgp_approved_by'               => $user->id,
                'tanggal_pemasangan'            => null,
                'panjang_pipa_pe'               => 10.00,
                'panjang_casing_crossing_sr'    => 1.50,
                'nomor_sr'                      => null,
            ],
            // 5) Scheduled
            [
                'status'                        => SrData::STATUS_SCHEDULED,
                'ai_overall_status'             => 'passed',
                'ai_checked_at'                 => now(),
                'tracer_approved_at'            => now()->subDays(3),
                'tracer_approved_by'            => $user->id,
                'cgp_approved_at'               => now()->subDays(2),
                'cgp_approved_by'               => $user->id,
                'tanggal_pemasangan'            => Carbon::now()->addDays(4),
                'panjang_pipa_pe'               => 20.00,
                'panjang_casing_crossing_sr'    => 2.50,
                'nomor_sr'                      => self::makeNomor('SR'),
            ],
            // 6) Completed
            [
                'status'                        => SrData::STATUS_COMPLETED,
                'ai_overall_status'             => 'passed',
                'ai_checked_at'                 => now(),
                'tracer_approved_at'            => now()->subDays(10),
                'tracer_approved_by'            => $user->id,
                'cgp_approved_at'               => now()->subDays(9),
                'cgp_approved_by'               => $user->id,
                'tanggal_pemasangan'            => Carbon::now()->subDays(1),
                'panjang_pipa_pe'               => 18.75,
                'panjang_casing_crossing_sr'    => 2.25,
                'nomor_sr'                      => self::makeNomor('SR'),
            ],
        ];

        $i = 0;
        foreach ($rows as $data) {
            $cp = $pelanggan[$i % $pelanggan->count()];
            SrData::create(array_merge($data, [
                'reff_id_pelanggan' => $cp->reff_id_pelanggan, // <- kunci baru
                'notes'             => $data['status'] === SrData::STATUS_DRAFT ? 'Contoh data draft SR' : null,
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
