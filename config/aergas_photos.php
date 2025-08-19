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
            'allow_submit_with_warnings' => true,  // KUNCI: izinkan submit meski ada warning

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

            // Warning messages berdasarkan score range
            'score_messages' => [
                'excellent' => 'Foto sangat baik dan memenuhi semua kriteria',
                'good'      => 'Foto baik dan dapat diterima',
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
        ]
    ]
];
