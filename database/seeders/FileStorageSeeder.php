<?php

namespace Database\Seeders;

use App\Models\FileStorage;
use App\Models\User;
use Illuminate\Database\Seeder;

class FileStorageSeeder extends Seeder
{
    public function run()
    {
        $skUser = User::where('role', 'sk')->first();
        $srUser = User::where('role', 'sr')->first();
        $mgrtUser = User::where('role', 'mgrt')->first();

        $fileStorages = [
            // SK Files
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'field_name' => 'foto_berita_acara_url',
                'original_filename' => 'Berita_Acara_SK_AER001.jpg',
                'stored_filename' => 'sk_ba_aer001_20241201_001.jpg',
                'file_path' => '/storage/sk/AER001/foto_berita_acara.jpg',
                'google_drive_id' => '1BmVzJ8xK9Q2L5N3P7R8T4V6W9X1Y2Z3A',
                'mime_type' => 'image/jpeg',
                'file_size' => 2048576, // 2MB
                'file_hash' => hash('sha256', 'sk_ba_aer001_content'),
                'uploaded_by' => $skUser->id,
            ],
            [
                'reff_id_pelanggan' => 'AER001',
                'module_name' => 'sk',
                'field_name' => 'foto_pneumatic_sk_url',
                'original_filename' => 'Pneumatic_Test_AER001.jpg',
                'stored_filename' => 'sk_pneumatic_aer001_20241201_002.jpg',
                'file_path' => '/storage/sk/AER001/foto_pneumatic_sk.jpg',
                'google_drive_id' => '1CnWaK9xL0R3M6O4Q8S9U5W7X0Y2Z4B5C',
                'mime_type' => 'image/jpeg',
                'file_size' => 3145728, // 3MB
                'file_hash' => hash('sha256', 'sk_pneumatic_aer001_content'),
                'uploaded_by' => $skUser->id,
            ],

            // SR Files
            [
                'reff_id_pelanggan' => 'AER002',
                'module_name' => 'sr',
                'field_name' => 'foto_pneumatic_start_sr_url',
                'original_filename' => 'SR_Pneumatic_Start_AER002.jpg',
                'stored_filename' => 'sr_pneumatic_start_aer002_20241130_001.jpg',
                'file_path' => '/storage/sr/AER002/foto_pneumatic_start.jpg',
                'google_drive_id' => '1DoXbL0yM1S4N7P5R9T0V6X8Y1Z3C6D7E',
                'mime_type' => 'image/jpeg',
                'file_size' => 2621440, // 2.5MB
                'file_hash' => hash('sha256', 'sr_pneumatic_start_aer002_content'),
                'uploaded_by' => $srUser->id,
            ],
            [
                'reff_id_pelanggan' => 'AER002',
                'module_name' => 'sr',
                'field_name' => 'foto_isometrik_sr_url',
                'original_filename' => 'Isometrik_Drawing_SR_AER002.pdf',
                'stored_filename' => 'sr_isometrik_aer002_20241130_002.pdf',
                'file_path' => '/storage/sr/AER002/foto_isometrik_sr.pdf',
                'google_drive_id' => '1EpYcM1zN2T5O8Q6S0U7W9X1Y4Z5D8E9F',
                'mime_type' => 'application/pdf',
                'file_size' => 1048576, // 1MB
                'file_hash' => hash('sha256', 'sr_isometrik_aer002_content'),
                'uploaded_by' => $srUser->id,
            ],

            // MGRT Files
            [
                'reff_id_pelanggan' => 'AER005',
                'module_name' => 'mgrt',
                'field_name' => 'foto_mgrt_url',
                'original_filename' => 'MGRT_Device_AER005.jpg',
                'stored_filename' => 'mgrt_device_aer005_20241125_001.jpg',
                'file_path' => '/storage/mgrt/AER005/foto_mgrt.jpg',
                'google_drive_id' => '1FqZdN2aO3U6P9R7T1V8X0Y2Z6E9F0G1H',
                'mime_type' => 'image/jpeg',
                'file_size' => 1572864, // 1.5MB
                'file_hash' => hash('sha256', 'mgrt_device_aer005_content'),
                'uploaded_by' => $mgrtUser->id,
            ],
            [
                'reff_id_pelanggan' => 'AER005',
                'module_name' => 'mgrt',
                'field_name' => 'foto_pondasi_url',
                'original_filename' => 'Foundation_MGRT_AER005.jpg',
                'stored_filename' => 'mgrt_foundation_aer005_20241125_002.jpg',
                'file_path' => '/storage/mgrt/AER005/foto_pondasi.jpg',
                'google_drive_id' => '1GrAeO3bP4V7Q0S8U2W9X1Y3Z7F0G2H3I',
                'mime_type' => 'image/jpeg',
                'file_size' => 2097152, // 2MB
                'file_hash' => hash('sha256', 'mgrt_foundation_aer005_content'),
                'uploaded_by' => $mgrtUser->id,
            ]
        ];

        foreach ($fileStorages as $data) {
            FileStorage::create($data);
        }

        $this->command->info('Created ' . count($fileStorages) . ' file storage records successfully.');
    }
}
