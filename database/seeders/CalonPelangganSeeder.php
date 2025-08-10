<?php

namespace Database\Seeders;

use App\Models\CalonPelanggan;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CalonPelangganSeeder extends Seeder
{
    public function run()
    {
        $pelanggan = [
            [
                'reff_id_pelanggan' => 'AER001',
                'nama_pelanggan' => 'Budi Santoso',
                'alamat' => 'Jl. Merdeka No. 123, RT 05/02, Kelurahan Menteng, Jakarta Pusat',
                'no_telepon' => '081234567890',
                'status' => 'validated',
                'progress_status' => 'sk',
                'keterangan' => 'Pelanggan baru dengan lokasi strategis',
                'wilayah_area' => 'Jakarta Pusat',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(10),
            ],
            [
                'reff_id_pelanggan' => 'AER002',
                'nama_pelanggan' => 'Siti Nurhaliza',
                'alamat' => 'Jl. Sudirman No. 456, RT 02/01, Kelurahan Bogor Tengah, Bogor',
                'no_telepon' => '081234567891',
                'status' => 'in_progress',
                'progress_status' => 'sr',
                'keterangan' => 'Proses berjalan lancar',
                'wilayah_area' => 'Bogor',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(8),
            ],
            [
                'reff_id_pelanggan' => 'AER003',
                'nama_pelanggan' => 'Ahmad Dahlan',
                'alamat' => 'Jl. Kebon Jeruk No. 789, RT 01/03, Kelurahan Kebon Jeruk, Jakarta Barat',
                'no_telepon' => '081234567892',
                'status' => 'validated',
                'progress_status' => 'validasi',
                'keterangan' => 'Menunggu konfirmasi jadwal survei',
                'wilayah_area' => 'Jakarta Barat',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(5),
            ],
            [
                'reff_id_pelanggan' => 'AER004',
                'nama_pelanggan' => 'Rina Melati',
                'alamat' => 'Jl. Cikini Raya No. 321, RT 04/02, Kelurahan Cikini, Jakarta Pusat',
                'no_telepon' => '081234567893',
                'status' => 'in_progress',
                'progress_status' => 'gas_in',
                'keterangan' => 'Tahap akhir instalasi',
                'wilayah_area' => 'Jakarta Pusat',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(20),
            ],
            [
                'reff_id_pelanggan' => 'AER005',
                'nama_pelanggan' => 'Warung Makan Sederhana',
                'alamat' => 'Jl. Pasar Minggu No. 567, RT 03/01, Kelurahan Pasar Minggu, Jakarta Selatan',
                'no_telepon' => '081234567894',
                'status' => 'validated',
                'progress_status' => 'mgrt',
                'keterangan' => 'Pelanggan komersial - warung makan',
                'wilayah_area' => 'Jakarta Selatan',
                'jenis_pelanggan' => 'komersial',
                'tanggal_registrasi' => Carbon::now()->subDays(15),
            ],
            [
                'reff_id_pelanggan' => 'AER006',
                'nama_pelanggan' => 'Toko Kelontong Berkah',
                'alamat' => 'Jl. Raya Depok No. 890, RT 02/05, Kelurahan Pancoran Mas, Depok',
                'no_telepon' => '081234567895',
                'status' => 'lanjut',
                'progress_status' => 'done',
                'keterangan' => 'Instalasi selesai, sudah beroperasi',
                'wilayah_area' => 'Depok',
                'jenis_pelanggan' => 'komersial',
                'tanggal_registrasi' => Carbon::now()->subDays(30),
            ],
            [
                'reff_id_pelanggan' => 'AER007',
                'nama_pelanggan' => 'Pak Joko Susilo',
                'alamat' => 'Jl. Veteran No. 234, RT 01/02, Kelurahan Veteran, Tangerang',
                'no_telepon' => '081234567896',
                'status' => 'batal',
                'progress_status' => 'batal',
                'keterangan' => 'Dibatalkan karena lokasi tidak memungkinkan',
                'wilayah_area' => 'Tangerang',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(12),
            ],
            [
                'reff_id_pelanggan' => 'AER008',
                'nama_pelanggan' => 'Ibu Sari Dewi',
                'alamat' => 'Jl. Kemang Raya No. 456, RT 06/03, Kelurahan Kemang, Jakarta Selatan',
                'no_telepon' => '081234567897',
                'status' => 'pending',
                'progress_status' => 'validasi',
                'keterangan' => 'Menunggu kelengkapan dokumen',
                'wilayah_area' => 'Jakarta Selatan',
                'jenis_pelanggan' => 'residensial',
                'tanggal_registrasi' => Carbon::now()->subDays(3),
            ]
        ];

        foreach ($pelanggan as $data) {
            CalonPelanggan::create($data);
        }

        $this->command->info('Created ' . count($pelanggan) . ' calon pelanggan successfully.');
    }
}
