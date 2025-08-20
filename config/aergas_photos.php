<?php
// config/aergas_photos.php - Complete configuration for SK & SR

return [
    // File upload limits
    'limits' => [
        'max_bytes' => (int) env('PHOTO_MAX_SIZE_BYTES', 10240 * 1024), // 10MB
        'allowed_mime_types' => ['image/jpeg','image/png','image/jpg','image/webp','application/pdf'],
    ],

    // File naming pattern
    'naming' => [
        'pattern' => env('AERGAS_PHOTO_NAMING', 'reff_slot_ts'),
    ],

    // AI Validation thresholds
    'ai_thresholds' => [
        'auto_pass_score' => 85,    // Score >= 85% = auto pass
        'warning_score'   => 70,    // Score 70-84% = warning (masih bisa submit)
        'reject_score'    => 50,    // Score < 50% = strong warning (tetap bisa submit tapi perlu review)
    ],

    // Module configurations
    'modules' => [

        // ====================== SK MODULE ======================
        'SK' => [
            'min_required_slots' => 4,
            'replace_same_slot' => true,
            'allow_submit_with_warnings' => true,

            'slots' => [

                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test START dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAUGE/MANOMETER (30 poin) - Apakah terlihat dan dapat dibaca?
                                2. FORM/PAPAN INFORMASI (25 poin) - Apakah ada dokumen/form terlihat?
                                3. SETUP PNEUMATIC (20 poin) - Apakah peralatan terpasang dengan benar?
                                4. WATERMARK KAMERA (15 poin) - Apakah ada cap waktu/lokasi dari HP?
                                5. KUALITAS FOTO (10 poin) - Apakah foto cukup jelas untuk diverifikasi?

                                PANDUAN PENILAIAN:
                                - 90-100: Semua elemen terlihat jelas dan lengkap
                                - 75-89: Sebagian besar elemen ada, mungkin ada 1-2 yang kurang optimal
                                - 60-74: Elemen utama ada tapi beberapa tidak jelas atau hilang
                                - 40-59: Hanya sebagian elemen yang teridentifikasi
                                - 0-39: Elemen penting tidak terlihat atau foto tidak sesuai

                                BERIKAN SKOR REALISTIS berdasarkan apa yang benar-benar terlihat.
                                JANGAN terlalu ketat - fokus pada IDENTIFIKASI OBJEK bukan kesempurnaan.',
                ],

                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test FINISH dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAUGE/MANOMETER (25 poin) - Apakah terlihat dan dapat dibaca?
                                2. FORM/PAPAN INFORMASI (25 poin) - Apakah ada dokumen/form terlihat?
                                3. FINISH TIME TERISI (25 poin) - Apakah kolom waktu selesai sudah diisi?
                                4. SETUP PNEUMATIC (15 poin) - Apakah peralatan masih terpasang?
                                5. WATERMARK KAMERA (10 poin) - Apakah ada cap waktu/lokasi dari HP?

                                PANDUAN PENILAIAN:
                                - 90-100: Semua elemen ada, termasuk Finish Time terisi jelas
                                - 75-89: Elemen utama ada, Finish Time mungkin ada tapi tidak terlalu jelas
                                - 60-74: Sebagian besar ada tapi Finish Time kosong atau tidak jelas
                                - 40-59: Elemen dasar ada tapi beberapa penting hilang
                                - 0-39: Elemen kritis tidak ada atau foto tidak sesuai

                                CATATAN: Finish Time kosong = maksimal skor 70
                                BERIKAN SKOR OBJEKTIF berdasarkan kelengkapan yang terlihat.',
                ],

                'valve' => [
                    'label' => 'Foto Valve SK',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto valve gas dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. HANDLE/TUAS VALVE (40 poin) - Apakah handle valve terlihat dan dapat diidentifikasi?
                                2. BODY VALVE (30 poin) - Apakah badan valve terlihat jelas?
                                3. SAMBUNGAN PIPA (20 poin) - Apakah koneksi ke pipa terlihat?
                                4. KUALITAS FOTO (10 poin) - Apakah foto cukup jelas untuk verifikasi?

                                PANDUAN PENILAIAN:
                                - 90-100: Handle jelas, valve utuh, sambungan terlihat, foto tajam
                                - 75-89: Handle terlihat, valve dapat diidentifikasi, cukup jelas
                                - 60-74: Handle sebagian terlihat atau valve agak blur tapi masih recognizable
                                - 40-59: Valve terlihat tapi handle tidak jelas atau foto blur
                                - 0-39: Valve tidak dapat diidentifikasi atau bukan foto valve

                                FOKUS: Handle tidak harus sempurna 100% terlihat, yang penting DAPAT DIIDENTIFIKASI sebagai valve.',
                ],

                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SK (TTD Lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'required' => false,
                    'prompt' => 'Analisis dokumen isometrik SK dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAMBAR ISOMETRIK (30 poin) - Apakah ada diagram/gambar teknis terlihat?
                                2. TANDA TANGAN PIC (25 poin) - Apakah ada TTD/paraf di area PIC?
                                3. TANDA TANGAN PELANGGAN (25 poin) - Apakah ada TTD/paraf di area Pelanggan?
                                4. TANDA TANGAN WASPANG (20 poin) - Apakah ada TTD/paraf di area Waspang?

                                PANDUAN PENILAIAN:
                                - 90-100: Gambar jelas + 3 tanda tangan lengkap dan jelas
                                - 75-89: Gambar ada + 2-3 tanda tangan terlihat (bisa berupa paraf/cap)
                                - 60-74: Gambar ada + 1-2 tanda tangan teridentifikasi
                                - 40-59: Dokumen terlihat tapi tanda tangan tidak jelas/tidak ada
                                - 0-39: Bukan dokumen isometrik atau tidak dapat dibaca

                                CATATAN: Tanda tangan bisa berupa coretan, paraf, cap, atau nama tulisan tangan.
                                TIDAK harus 3 TTD sempurna untuk mendapat skor tinggi.',
                ],

            ],

            'score_messages' => [
                'excellent' => 'Foto sangat baik dan memenuhi semua kriteria',
                'good'      => 'Foto baik dan dapat diterima',
                'warning'   => 'Foto dapat diterima namun ada beberapa elemen yang perlu diperbaiki',
                'poor'      => 'Foto masih dapat diproses namun sangat disarankan untuk diperbaiki',
            ]
        ],

        // ====================== SR MODULE ======================
        'SR' => [
            'min_required_slots' => 7,
            'replace_same_slot' => true,
            'allow_submit_with_warnings' => true,

            'slots' => [

                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SR',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test START SR dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAUGE/MANOMETER (30 poin) - Apakah terlihat dan dapat dibaca?
                                2. FORM/PAPAN INFORMASI (25 poin) - Apakah ada dokumen/form terlihat?
                                3. SETUP PNEUMATIC (20 poin) - Apakah peralatan terpasang dengan benar?
                                4. WATERMARK KAMERA (15 poin) - Apakah ada cap waktu/lokasi dari HP?
                                5. KUALITAS FOTO (10 poin) - Apakah foto cukup jelas untuk diverifikasi?

                                PANDUAN PENILAIAN:
                                - 90-100: Semua elemen terlihat jelas dan lengkap
                                - 75-89: Sebagian besar elemen ada, mungkin ada 1-2 yang kurang optimal
                                - 60-74: Elemen utama ada tapi beberapa tidak jelas atau hilang
                                - 40-59: Hanya sebagian elemen yang teridentifikasi
                                - 0-39: Elemen penting tidak terlihat atau foto tidak sesuai

                                BERIKAN SKOR REALISTIS berdasarkan apa yang benar-benar terlihat.',
                ],

                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SR',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pneumatic test FINISH SR dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAUGE/MANOMETER (25 poin) - Apakah terlihat dan dapat dibaca?
                                2. FORM/PAPAN INFORMASI (25 poin) - Apakah ada dokumen/form terlihat?
                                3. FINISH TIME TERISI (25 poin) - Apakah kolom waktu selesai sudah diisi?
                                4. SETUP PNEUMATIC (15 poin) - Apakah peralatan masih terpasang?
                                5. WATERMARK KAMERA (10 poin) - Apakah ada cap waktu/lokasi dari HP?

                                PANDUAN PENILAIAN:
                                - 90-100: Semua elemen ada, termasuk Finish Time terisi jelas
                                - 75-89: Elemen utama ada, Finish Time mungkin ada tapi tidak terlalu jelas
                                - 60-74: Sebagian besar ada tapi Finish Time kosong atau tidak jelas
                                - 40-59: Elemen dasar ada tapi beberapa penting hilang
                                - 0-39: Elemen kritis tidak ada atau foto tidak sesuai

                                CATATAN: Finish Time kosong = maksimal skor 70',
                ],

                'jenis_tapping' => [
                    'label' => 'Foto Jenis Tapping',
                    'accept' => ['image/*'],
                    'required' => true,
                    'requires' => ['jenis_tapping'],
                    'prompt' => 'Analisis foto jenis tapping dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. TAPPING SADDLE TERLIHAT (40 poin) - Apakah komponen tapping saddle dapat diidentifikasi?
                                2. UKURAN/MARKING (30 poin) - Apakah ada marking ukuran atau label yang terlihat?
                                3. POSISI TAPPING (20 poin) - Apakah posisi tapping pada pipa utama terlihat?
                                4. KUALITAS FOTO (10 poin) - Apakah foto cukup jelas untuk verifikasi?

                                PANDUAN PENILAIAN:
                                - 90-100: Tapping saddle jelas, ukuran/marking terlihat, posisi tepat
                                - 75-89: Tapping teridentifikasi, sebagian marking ada, posisi cukup jelas
                                - 60-74: Tapping dapat dikenali tapi detail kurang jelas
                                - 40-59: Tapping terlihat tapi informasi detail minim
                                - 0-39: Tapping tidak dapat diidentifikasi atau foto tidak sesuai

                                VALIDASI JENIS: Cocokkan dengan jenis tapping yang dipilih di form.
                                KONTEKS: Pastikan ukuran sesuai dengan standar (63x20, 90x20, dll).',
                ],

                'kedalaman' => [
                    'label' => 'Foto Kedalaman',
                    'accept' => ['image/*'],
                    'required' => false,
                    'prompt' => 'Analisis foto pengukuran kedalaman dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. ALAT UKUR TERLIHAT (35 poin) - Apakah meteran/penggaris/alat ukur terlihat?
                                2. ANGKA PENGUKURAN (35 poin) - Apakah nilai kedalaman dapat dibaca?
                                3. GALIAN/LUBANG (20 poin) - Apakah area galian/lubang terlihat jelas?
                                4. KUALITAS FOTO (10 poin) - Apakah foto cukup terang dan fokus?

                                PANDUAN PENILAIAN:
                                - 90-100: Alat ukur jelas, angka terbaca dengan baik, konteks galian terlihat
                                - 75-89: Alat ukur ada, angka dapat dibaca meski tidak sempurna
                                - 60-74: Alat ukur terlihat tapi angka agak sulit dibaca
                                - 40-59: Ada upaya pengukuran tapi hasil tidak jelas
                                - 0-39: Tidak ada alat ukur atau tidak dapat mengidentifikasi pengukuran

                                FOKUS: Bukti pengukuran kedalaman yang dapat diverifikasi.',
                ],

                'mgrt' => [
                    'label' => 'Foto MGRT',
                    'accept' => ['image/*'],
                    'required' => false,
                    'requires' => ['no_seri_mgrt'],
                    'prompt' => 'Analisis foto MGRT (Meter Gas Rumah Tangga) dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. BODY METER TERLIHAT (30 poin) - Apakah body/housing meter dapat diidentifikasi?
                                2. NO SERI/LABEL (30 poin) - Apakah nomor seri atau label terlihat dan terbaca?
                                3. BRAND/MERK (20 poin) - Apakah merk/brand meter dapat diidentifikasi?
                                4. SAMBUNGAN (20 poin) - Apakah inlet/outlet connections terlihat?

                                PANDUAN PENILAIAN:
                                - 90-100: Meter jelas, no seri terbaca, brand terlihat, sambungan lengkap
                                - 75-89: Meter teridentifikasi, no seri/brand sebagian terbaca
                                - 60-74: Meter dapat dikenali tapi detail marking kurang jelas
                                - 40-59: Meter terlihat tapi informasi spesifik sulit dibaca
                                - 0-39: Meter tidak dapat diidentifikasi atau foto tidak sesuai

                                VALIDASI: Cocokkan nomor seri dengan yang diinput di form.
                                FOKUS: Pastikan foto cukup dekat untuk verifikasi serial number.',
                ],

                'pondasi' => [
                    'label' => 'Foto Pondasi',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto pondasi dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. STRUKTUR PONDASI (40 poin) - Apakah struktur pondasi/tiang terlihat jelas?
                                2. MATERIAL PONDASI (30 poin) - Apakah jenis material pondasi dapat diidentifikasi?
                                3. PEMASANGAN (20 poin) - Apakah posisi dan pemasangan terlihat proper?
                                4. KUALITAS FOTO (10 poin) - Apakah foto cukup jelas untuk dokumentasi?

                                PANDUAN PENILAIAN:
                                - 90-100: Pondasi jelas, material teridentifikasi, pemasangan proper
                                - 75-89: Pondasi terlihat, sebagian detail material/pemasangan ada
                                - 60-74: Pondasi dapat dikenali tapi detail kurang optimal
                                - 40-59: Struktur terlihat tapi detail konstruksi tidak jelas
                                - 0-39: Pondasi tidak dapat diidentifikasi atau foto tidak sesuai

                                FOKUS: Dokumentasi struktur pendukung untuk sistem gas.',
                ],

                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SR (TTD Lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'required' => true,
                    'prompt' => 'Analisis dokumen isometrik SR dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. GAMBAR ISOMETRIK (30 poin) - Apakah ada diagram/gambar teknis terlihat?
                                2. TANDA TANGAN PIC (25 poin) - Apakah ada TTD/paraf di area PIC?
                                3. TANDA TANGAN PELANGGAN (25 poin) - Apakah ada TTD/paraf di area Pelanggan?
                                4. TANDA TANGAN WASPANG (20 poin) - Apakah ada TTD/paraf di area Waspang?

                                PANDUAN PENILAIAN:
                                - 90-100: Gambar jelas + 3 tanda tangan lengkap dan jelas
                                - 75-89: Gambar ada + 2-3 tanda tangan terlihat (bisa berupa paraf/cap)
                                - 60-74: Gambar ada + 1-2 tanda tangan teridentifikasi
                                - 40-59: Dokumen terlihat tapi tanda tangan tidak jelas/tidak ada
                                - 0-39: Bukan dokumen isometrik atau tidak dapat dibaca

                                CATATAN: Tanda tangan bisa berupa coretan, paraf, cap, atau nama tulisan tangan.
                                KHUSUS SR: Periksa referensi material sesuai BOM SR.',
                ],

            ],

            'score_messages' => [
                'excellent' => 'Foto sangat baik dan memenuhi semua kriteria SR',
                'good'      => 'Foto baik dan dapat diterima untuk SR',
                'warning'   => 'Foto dapat diterima namun ada beberapa elemen yang perlu diperbaiki',
                'poor'      => 'Foto masih dapat diproses namun sangat disarankan untuk diperbaiki',
            ]
        ],

    ],

    // Aliases untuk backward compatibility
    'aliases' => [
        'SK' => [
            'foto_pneumatic_start_sk_url' => 'pneumatic_start',
            'foto_pneumatic_finish_sk_url' => 'pneumatic_finish',
            'foto_valve_sk_url' => 'valve',
            'foto_isometrik_scan_sk_url' => 'isometrik_scan',
        ],
        'SR' => [
            'foto_pneumatic_start_sr_url' => 'pneumatic_start',
            'foto_pneumatic_finish_sr_url' => 'pneumatic_finish',
            'foto_jenis_tapping_url' => 'jenis_tapping',
            'foto_kedalaman_url' => 'kedalaman',
            'foto_mgrt_url' => 'mgrt',
            'foto_pondasi_url' => 'pondasi',
            'foto_isometrik_scan_sr_url' => 'isometrik_scan',
        ]
    ]
];
