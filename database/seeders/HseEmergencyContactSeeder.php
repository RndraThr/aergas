<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HseEmergencyContact;

class HseEmergencyContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [
            // Emergency Response Team
            [
                'jabatan' => 'SERT Leader',
                'nama_petugas' => 'Ahmad Yani',
                'nomor_telepon' => '0812-3456-7890',
                'kategori' => 'emergency_response',
                'urutan' => 1,
                'is_active' => true,
            ],
            [
                'jabatan' => 'SERT Member 1',
                'nama_petugas' => 'Budi Santoso',
                'nomor_telepon' => '0813-4567-8901',
                'kategori' => 'emergency_response',
                'urutan' => 2,
                'is_active' => true,
            ],
            [
                'jabatan' => 'SERT Member 2',
                'nama_petugas' => 'Citra Dewi',
                'nomor_telepon' => '0814-5678-9012',
                'kategori' => 'emergency_response',
                'urutan' => 3,
                'is_active' => true,
            ],

            // Medical / P3K
            [
                'jabatan' => 'P3K Leader',
                'nama_petugas' => 'Dr. Siti Aminah',
                'nomor_telepon' => '0815-6789-0123',
                'kategori' => 'medical',
                'urutan' => 1,
                'is_active' => true,
            ],
            [
                'jabatan' => 'P3K Member',
                'nama_petugas' => 'Eko Prasetyo',
                'nomor_telepon' => '0816-7890-1234',
                'kategori' => 'medical',
                'urutan' => 2,
                'is_active' => true,
            ],

            // Security
            [
                'jabatan' => 'Security Coordinator',
                'nama_petugas' => 'Faisal Rahman',
                'nomor_telepon' => '0817-8901-2345',
                'kategori' => 'security',
                'urutan' => 1,
                'is_active' => true,
            ],

            // External Emergency
            [
                'jabatan' => 'Ambulans',
                'nama_petugas' => 'Layanan Darurat',
                'nomor_telepon' => '118',
                'kategori' => 'emergency_response',
                'urutan' => 99,
                'is_active' => true,
            ],
            [
                'jabatan' => 'Pemadam Kebakaran',
                'nama_petugas' => 'Damkar Sleman',
                'nomor_telepon' => '113',
                'kategori' => 'emergency_response',
                'urutan' => 98,
                'is_active' => true,
            ],
            [
                'jabatan' => 'Polisi',
                'nama_petugas' => 'Polsek Terdekat',
                'nomor_telepon' => '110',
                'kategori' => 'security',
                'urutan' => 99,
                'is_active' => true,
            ],

            // Hospital
            [
                'jabatan' => 'Rumah Sakit',
                'nama_petugas' => 'RS PKU Muhammadiyah',
                'nomor_telepon' => '0274-445454',
                'kategori' => 'medical',
                'urutan' => 99,
                'is_active' => true,
            ],
        ];

        foreach ($contacts as $contact) {
            HseEmergencyContact::create($contact);
        }
    }
}
