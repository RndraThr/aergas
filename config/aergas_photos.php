<?php
// config/aergas_photos.php - Updated with custom prompts

return [
    // File upload limits
    'limits' => [
        'max_bytes' => (int) env('PHOTO_MAX_SIZE_BYTES', 10240 * 1024), // 10MB
        'allowed_mime_types' => ['image/jpeg','image/png','image/jpg','image/webp','application/pdf'],
    ],

    // File naming pattern
    'naming' => [
        'pattern' => env('AERGAS_PHOTO_NAMING', 'reff_slot_ts'), // reff_slot_ts | reff_slot | ts_reff_slot_orig
    ],

    // Module configurations
    'modules' => [

        // ====================== SK MODULE ======================
        'SK' => [
            'min_required_slots' => 5,
            'replace_same_slot' => true,

            'slots' => [

                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test START ini dengan kriteria SPESIFIK:

                                YANG HARUS ADA:
                                1. GAUGE/MANOMETER terlihat dengan angka yang dapat dibaca (tidak perlu 100% tajam, asalkan terlihat)
                                2. FORM/PAPAN INFORMASI terlihat dalam foto (boleh sebagian)
                                3. PIPA atau SAMBUNGAN pneumatic terlihat
                                4. SETUP pneumatic test tampak terpasang dengan benar
                                5. CAP AIR / WATERMARK kamera HP (tanggal, jam, lokasi, dll.) terlihat pada foto

                                EVALUASI:
                                - Apakah gauge bulat dengan jarum terlihat? Jika ya = LULUS
                                - Apakah ada form/papan bertuliskan informasi? Jika ya = LULUS
                                - Apakah setup pneumatic tampak normal? Jika ya = LULUS
                                - Apakah watermark kamera HP (pojok bawah) terlihat? Jika ya = LULUS
                                - Jangan terlalu strict pada ketajaman - fokus pada VISIBILITY

                                INSTRUKSI FINAL:
                                - Jika gauge terlihat + form ada + setup proper + watermark ada = TERIMA
                                - Jika salah satu elemen utama tidak terlihat sama sekali = TOLAK dengan alasan spesifik
                                - JANGAN tolak hanya karena "sedikit blur" jika objek masih dapat diidentifikasi

                                Berikan penilaian yang PRAKTIS dan KONSISTEN.',
                ],


                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test FINISH ini dengan kriteria SPESIFIK:

                                YANG HARUS ADA:
                                1. GAUGE/MANOMETER terlihat dengan angka yang dapat dibaca (tidak perlu 100% tajam, asalkan terlihat)
                                2. FORM/PAPAN INFORMASI terlihat dalam foto (boleh sebagian)
                                3. PIPA atau SAMBUNGAN pneumatic terlihat
                                4. SETUP pneumatic test tampak terpasang dengan benar
                                5. CAP AIR / WATERMARK kamera HP (tanggal, jam, lokasi, dll.) terlihat pada foto
                                6. Bagian "Finish Time" pada form harus TERISI (tidak boleh kosong)

                                EVALUASI:
                                - Apakah gauge bulat dengan jarum terlihat? Jika ya = LULUS
                                - Apakah ada form/papan bertuliskan informasi? Jika ya = LULUS
                                - Apakah setup pneumatic tampak normal? Jika ya = LULUS
                                - Apakah watermark kamera HP (pojok bawah) terlihat? Jika ya = LULUS
                                - Apakah kolom Finish Time pada form terisi jam yang jelas? Jika ya = LULUS
                                - Jangan terlalu strict pada ketajaman - fokus pada VISIBILITY

                                INSTRUKSI FINAL:
                                - Jika gauge terlihat + form ada + setup proper + watermark ada + Finish Time terisi = TERIMA
                                - Jika salah satu elemen utama tidak terlihat sama sekali = TOLAK dengan alasan spesifik
                                - Jika Finish Time kosong = TOLAK dengan alasan "Finish Time tidak terisi"
                                - JANGAN tolak hanya karena "sedikit blur" jika objek masih dapat diidentifikasi

                                Berikan penilaian yang PRAKTIS dan KONSISTEN.',
                ],


                'valve' => [
                    'label' => 'Foto Valve SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Periksa foto valve gas ini dengan teliti:

                                KRITERIA WAJIB:
                                1. Valve gas terlihat dengan jelas (bukan samar atau tertutup)
                                2. Handle/tuas valve terlihat dan dapat diidentifikasi
                                3. Foto tidak blur - detail valve harus jelas
                                4. Posisi valve (terbuka/tertutup) dapat dilihat
                                5. Sambungan pipa ke valve terlihat
                                6. CAP AIR / WATERMARK kamera HP (tanggal, jam, lokasi, dll.) terlihat pada foto

                                INSTRUKSI:
                                - Jika valve tidak terlihat jelas atau tertutup objek lain: TOLAK dengan alasan "valve tidak terlihat jelas"
                                - Jika handle tidak terlihat: TOLAK dengan alasan "handle valve tidak terlihat"
                                - Jika foto blur: TOLAK dengan alasan "foto tidak jelas"
                                - Jika watermark kamera HP tidak ada: TOLAK dengan alasan "watermark kamera tidak ada"
                                - Jika semua valve components + watermark terlihat jelas: TERIMA

                                Valve adalah komponen safety critical, jadi harus benar-benar jelas terlihat.',
                ],


                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SK (TTD Lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'required' => true,
                    'prompt' => 'Periksa dokumen isometrik SK ini dengan SANGAT TELITI:

                                KRITERIA WAJIB:
                                1. HARUS ADA 3 TANDA TANGAN:
                                - Tanda tangan PIC (biasanya di kiri atau atas)
                                - Tanda tangan PELANGGAN (biasanya di tengah)
                                - Tanda tangan WASPANG (biasanya di kanan atau bawah)
                                2. CAP AIR / WATERMARK kamera HP (tanggal, jam, lokasi, dll.) terlihat pada foto

                                CARA PERIKSA:
                                - Lihat seluruh dokumen, cari area tanda tangan
                                - Hitung jumlah tanda tangan yang ADA (bukan kotak kosong)
                                - Tanda tangan bisa berupa coretan tinta, nama tulis tangan, atau cap
                                - Pastikan watermark kamera HP terlihat jelas di pojok foto

                                INSTRUKSI KETAT:
                                - Jika hanya ada 2 tanda tangan atau kurang: TOLAK dengan alasan "tanda tangan tidak lengkap - harus ada 3 tanda tangan (PIC, PELANGGAN, WASPANG)"
                                - Jika watermark kamera HP tidak ada: TOLAK dengan alasan "watermark kamera tidak ada"
                                - Jika ada 3 tanda tangan yang jelas terisi + watermark terlihat: TERIMA
                                - Jika dokumen tidak jelas atau tidak bisa dibaca: TOLAK dengan alasan "dokumen tidak jelas"
                                - Jika ragu jumlah tanda tangan: TOLAK dengan alasan "tidak dapat memastikan kelengkapan tanda tangan"

                                Ini dokumen legal penting - harus benar-benar ada 3 tanda tangan + watermark kamera HP!',
                ],
            ]

        ],

        // ====================== SR MODULE ======================
        'SR' => [
            'min_required_slots' => 5,
            'replace_same_slot' => true,

            'slots' => [

                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SR',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Periksa foto pneumatic test START untuk SR ini:

KRITERIA WAJIB:
1. Foto tidak blur - gauge harus bisa dibaca
2. Pneumatic test equipment terlihat jelas
3. Gauge/manometer menunjukkan tekanan awal
4. Pipa atau sambungan yang akan ditest terlihat
5. Setup test terlihat proper dan aman

INSTRUKSI:
- Jika equipment tidak terlihat jelas: TOLAK dengan alasan "equipment pneumatic tidak jelas"
- Jika gauge tidak readable: TOLAK dengan alasan "gauge tidak dapat dibaca"
- Jika foto blur: TOLAK dengan alasan "foto tidak jelas"
- Jika setup terlihat tidak aman: TOLAK dengan alasan "setup tidak aman"
- Jika semua kriteria OK: TERIMA',
                ],

                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SR',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Periksa foto pneumatic test FINISH untuk SR ini:

KRITERIA WAJIB:
1. Foto tidak blur - hasil test harus jelas
2. Gauge menunjukkan hasil akhir test
3. Equipment masih terpasang dengan baik
4. Tidak ada tanda kebocoran atau masalah
5. Hasil test dapat diverifikasi

INSTRUKSI:
- Jika hasil test tidak jelas: TOLAK dengan alasan "hasil test tidak dapat diverifikasi"
- Jika gauge tidak menunjukkan hasil yang clear: TOLAK dengan alasan "pembacaan gauge tidak jelas"
- Jika terlihat ada kebocoran: TOLAK dengan alasan "terlihat ada masalah/kebocoran"
- Jika foto blur: TOLAK dengan alasan "foto tidak jelas"
- Jika hasil test jelas dan proper: TERIMA',
                ],

                'tapping_saddle' => [
                    'label' => 'Foto Jenis Tapping (Saddle)',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Periksa foto tapping saddle untuk SR ini:

KRITERIA WAJIB:
1. Tapping saddle terlihat dengan jelas
2. Ukuran/size marking pada saddle dapat dibaca
3. Pemasangan saddle terlihat proper dan rapi
4. Foto tidak blur - detail saddle harus jelas
5. Sambungan ke pipa utama terlihat

INSTRUKSI:
- Jika saddle tidak terlihat jelas: TOLAK dengan alasan "tapping saddle tidak terlihat jelas"
- Jika size marking tidak dapat dibaca: TOLAK dengan alasan "ukuran saddle tidak dapat diverifikasi"
- Jika pemasangan terlihat tidak proper: TOLAK dengan alasan "pemasangan saddle tidak proper"
- Jika foto blur: TOLAK dengan alasan "foto tidak jelas"
- Jika saddle terpasang dengan benar dan jelas: TERIMA',
                ],

                'kedalaman' => [
                    'label' => 'Foto Kedalaman Galian',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Periksa foto pengukuran kedalaman galian ini:

KRITERIA WAJIB:
1. Galian/lubang terlihat jelas
2. Alat ukur (pita ukur/penggaris) terlihat dan dapat dibaca
3. Kedalaman dapat diverifikasi dari alat ukur
4. Foto tidak blur - angka pada alat ukur harus jelas
5. Konteks galian untuk instalasi pipa terlihat

INSTRUKSI:
- Jika alat ukur tidak terlihat: TOLAK dengan alasan "alat ukur tidak terlihat"
- Jika angka kedalaman tidak dapat dibaca: TOLAK dengan alasan "kedalaman tidak dapat diverifikasi"
- Jika galian tidak terlihat jelas: TOLAK dengan alasan "galian tidak terlihat jelas"
- Jika foto blur: TOLAK dengan alasan "foto tidak jelas"
- Jika kedalaman dapat diverifikasi dengan jelas: TERIMA',
                ],

                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SR (TTD Lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'required' => true,
                    'prompt' => 'Periksa dokumen isometrik SR ini dengan SANGAT TELITI:

KRITERIA WAJIB - HARUS ADA 3 TANDA TANGAN:
1. Tanda tangan PETUGAS SR (biasanya di kiri atau atas)
2. Tanda tangan SUPERVISOR/TRACER (biasanya di tengah)
3. Tanda tangan PELANGGAN (biasanya di kanan atau bawah)

CARA PERIKSA:
- Scan seluruh dokumen untuk area tanda tangan
- Hitung jumlah tanda tangan yang BENAR-BENAR ADA
- Tanda tangan bisa coretan tinta, tulisan tangan, atau cap

INSTRUKSI KETAT:
- Jika kurang dari 3 tanda tangan: TOLAK dengan alasan "tanda tangan tidak lengkap - harus ada 3 tanda tangan (petugas, supervisor, pelanggan)"
- Jika ada 3 tanda tangan lengkap: TERIMA
- Jika dokumen tidak dapat dibaca: TOLAK dengan alasan "dokumen tidak jelas"
- Jika tidak yakin jumlah tanda tangan: TOLAK dengan alasan "tidak dapat memastikan kelengkapan tanda tangan"

Dokumen hukum penting - 3 tanda tangan WAJIB!',
                ],
            ],
        ],
    ],
];
