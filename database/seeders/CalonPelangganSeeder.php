<?php

namespace Database\Seeders;

use App\Models\CalonPelanggan;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CalonPelangganSeeder extends Seeder
{
    public function run(): void
    {
        $pelanggan = [
            [
                'reff_id_pelanggan' => '12434546',
                'nama_pelanggan'    => 'Budi Santoso',
                'alamat'            => 'Jl. Merdeka No. 123, RT 05/02, Kelurahan Menteng, Jakarta Pusat',
                'no_telepon'        => '081234567890',
                'status'            => 'lanjut',
                'progress_status'   => 'sk',
                'keterangan'        => 'Pelanggan baru dengan lokasi strategis',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Menteng',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(10),
            ],
            [
                'reff_id_pelanggan' => '35234545',
                'nama_pelanggan'    => 'Siti Nurhaliza',
                'alamat'            => 'Jl. Sudirman No. 456, RT 02/01, Kelurahan Bogor Tengah, Bogor',
                'no_telepon'        => '081234567891',
                'status'            => 'in_progress',
                'progress_status'   => 'sr',
                'keterangan'        => 'Proses berjalan lancar',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Bogor Tengah',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(8),
            ],
            [
                'reff_id_pelanggan' => '12342544',
                'nama_pelanggan'    => 'Ahmad Dahlan',
                'alamat'            => 'Jl. Kebon Jeruk No. 789, RT 01/03, Kelurahan Kebon Jeruk, Jakarta Barat',
                'no_telepon'        => '081234567892',
                'status'            => 'lanjut',
                'progress_status'   => 'validasi',
                'keterangan'        => 'Menunggu konfirmasi jadwal survei',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Kebon Jeruk',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(5),
            ],
            [
                'reff_id_pelanggan' => '08796785',
                'nama_pelanggan'    => 'Rina Melati',
                'alamat'            => 'Jl. Cikini Raya No. 321, RT 04/02, Kelurahan Cikini, Jakarta Pusat',
                'no_telepon'        => '081234567893',
                'status'            => 'in_progress',
                'progress_status'   => 'gas_in',
                'keterangan'        => 'Tahap akhir instalasi',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Cikini',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(20),
            ],
            [
                'reff_id_pelanggan' => '65765667',
                'nama_pelanggan'    => 'Warung Makan Sederhana',
                'alamat'            => 'Jl. Pasar Minggu No. 567, RT 03/01, Kelurahan Pasar Minggu, Jakarta Selatan',
                'no_telepon'        => '081234567894',
                'status'            => 'lanjut',
                'progress_status'   => 'gas_in',
                'keterangan'        => 'Pelanggan komersial - warung makan',
                'jenis_pelanggan'   => 'penetrasi',
                'kelurahan'         => 'Pasar Minggu',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(15),
            ],
            [
                'reff_id_pelanggan' => '75089708',
                'nama_pelanggan'    => 'Toko Kelontong Berkah',
                'alamat'            => 'Jl. Raya Depok No. 890, RT 02/05, Kelurahan Pancoran Mas, Depok',
                'no_telepon'        => '081234567895',
                'status'            => 'lanjut',
                'progress_status'   => 'done',
                'keterangan'        => 'Instalasi selesai, sudah beroperasi',
                'jenis_pelanggan'   => 'penetrasi',
                'kelurahan'         => 'Pancoran Mas',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(30),
            ],
            [
                'reff_id_pelanggan' => '00090878',
                'nama_pelanggan'    => 'Pak Joko Susilo',
                'alamat'            => 'Jl. Veteran No. 234, RT 01/02, Kelurahan Veteran, Tangerang',
                'no_telepon'        => '081234567896',
                'status'            => 'batal',
                'progress_status'   => 'batal',
                'keterangan'        => 'Dibatalkan karena lokasi tidak memungkinkan',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Veteran',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(12),
            ],
            [
                'reff_id_pelanggan' => '76764466',
                'nama_pelanggan'    => 'Ibu Sari Dewi',
                'alamat'            => 'Jl. Kemang Raya No. 456, RT 06/03, Kelurahan Kemang, Jakarta Selatan',
                'no_telepon'        => '081234567897',
                'status'            => 'pending',
                'progress_status'   => 'validasi',
                'keterangan'        => 'Menunggu kelengkapan dokumen',
                'jenis_pelanggan'   => 'pengembangan',
                'kelurahan'         => 'Kemang',
                'padukuhan'         => null,
                'tanggal_registrasi'=> Carbon::now()->subDays(3),
            ],
        ];

        foreach ($pelanggan as $data) {
            CalonPelanggan::create($data);
        }

        $this->command->info('Created ' . count($pelanggan) . ' calon pelanggan successfully.');
    }
}
