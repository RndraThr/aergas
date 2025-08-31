<?php
// config/aergas_photos.php - Complete configuration with GAS_IN module

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
                    'prompt' => 'Analisis foto tapping dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. OBJEK TAPPING TERLIHAT (60 poin) - Apakah ada komponen tapping yang dapat diidentifikasi?
                                2. KUALITAS FOTO (40 poin) - Apakah foto tidak blur dan cukup jelas?

                                PANDUAN PENILAIAN:
                                - 90-100: Objek tapping sangat jelas, foto tajam dan terang
                                - 75-89: Objek tapping teridentifikasi, foto cukup jelas
                                - 60-74: Objek tapping dapat dikenali meskipun agak blur
                                - 40-59: Ada objek yang mungkin tapping tapi tidak terlalu jelas
                                - 0-39: Tidak ada objek tapping yang dapat diidentifikasi atau foto sangat blur

                                FOKUS UTAMA: Foto tidak blur dan ada objek yang dapat diidentifikasi sebagai bagian dari tapping.',
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
                                1. "Gambar ini menampilkan sebuah pipa berwarna kuning yang berdiri tegak.Bagian bawah pipa tersebut tertancap atau menempel pada pondasi berbentuk balok berwarna abu-abu. fokusnya pada pondasi berbentuk balok berwarna abu2. abaikan dokumeen tagging, jika ada pipa kuning tetapi pondasi balok berwarna abu2 tidak terlihat maka gagal" (60 poin) -
                                2. KUALITAS FOTO (40 poin) - Pada bagian pondasi memang sedikit blur tetapi warna dan bentuk masih bisa terlihat. anggap pondasi berhasil teridentifikasi

                                PANDUAN PENILAIAN:
                                - 90-100: Pondasi sangat jelas terlihat, foto tajam dan terang
                                - 75-89: Pondasi teridentifikasi dengan baik, foto cukup jelas
                                - 60-74: Pondasi dapat dikenali meskipun agak blur
                                - 40-59: Ada struktur yang mungkin pondasi tapi tidak terlalu jelas
                                - 0-39: Tidak ada struktur pondasi yang dapat diidentifikasi atau foto sangat blur

                                FOKUS UTAMA: Foto tidak blur dan ada objek yang dapat diidentifikasi sebagai pondasi.',
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

        // ====================== GAS_IN MODULE ======================
        'GAS_IN' => [
            'min_required_slots' => 4,
            'replace_same_slot' => true,
            'allow_submit_with_warnings' => true,

            'slots' => [

                'ba_gas_in' => [
                    'label' => 'Berita Acara Gas In',
                    'accept' => ['image/*', 'application/pdf'],
                    'required' => true,
                    'prompt' => 'Analisis dokumen Berita Acara Gas In dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. HEADER/JUDUL DOKUMEN (25 poin) - Apakah ada judul "Berita Acara" atau "Gas In"?
                                2. TANDA TANGAN PETUGAS (25 poin) - Apakah ada TTD/paraf petugas gas in?
                                3. TANDA TANGAN PELANGGAN (25 poin) - Apakah ada TTD/paraf pelanggan?
                                4. DATA TEKNIS (15 poin) - Apakah ada data teknis (pressure, flow, dll)?
                                5. TANGGAL/WAKTU (10 poin) - Apakah ada informasi tanggal Gas In?

                                PANDUAN PENILAIAN:
                                - 90-100: Semua elemen lengkap, dokumen resmi dengan TTD lengkap
                                - 75-89: Dokumen ada, sebagian besar TTD dan data teknis terisi
                                - 60-74: Dokumen teridentifikasi, beberapa TTD atau data ada
                                - 40-59: Dokumen Gas In terlihat tapi informasi minim
                                - 0-39: Bukan dokumen Gas In atau tidak dapat dibaca

                                CATATAN: TTD bisa berupa tanda tangan, paraf, cap, atau nama tertulis.
                                FOKUS: Kelengkapan dokumen untuk validasi Gas In.',
                ],

                'foto_bubble_test' => [
                    'label' => 'Foto Bubble Test (Uji Kebocoran)',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto bubble test dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. SABUN/CAIRAN UJI (30 poin) - Apakah terlihat cairan sabun atau foam?
                                2. AREA SAMBUNGAN (30 poin) - Apakah sambungan pipa/fitting terlihat?
                                3. TANGAN/ALAT APLIKATOR (20 poin) - Apakah terlihat tangan/kuas yang mengaplikasikan?
                                4. KONDISI HASIL UJI (20 poin) - Apakah hasilnya dapat dinilai (gelembung/tidak)?

                                PANDUAN PENILAIAN:
                                - 90-100: Cairan sabun jelas, sambungan terlihat, proses aplikasi obvious
                                - 75-89: Foam/sabun teridentifikasi, area sambungan cukup jelas
                                - 60-74: Ada cairan di area sambungan yang kemungkinan bubble test
                                - 40-59: Area sambungan terlihat tapi bubble test tidak jelas
                                - 0-39: Tidak ada indikasi bubble test atau foto tidak sesuai

                                FOKUS: Dokumentasi proses pengujian kebocoran pada sambungan.
                                HASIL: Tidak ada gelembung = bagus (tidak bocor).',
                ],

                'foto_regulator' => [
                    'label' => 'Foto Regulator Service',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto regulator service dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. BODY REGULATOR (35 poin) - Apakah housing/body regulator dapat diidentifikasi?
                                2. INLET/OUTLET CONNECTION (25 poin) - Apakah sambungan input/output terlihat?
                                3. PRESSURE GAUGE (25 poin) - Apakah ada gauge/manometer terpasang?
                                4. LABEL/MARKING (15 poin) - Apakah ada label teknis atau brand terlihat?

                                PANDUAN PENILAIAN:
                                - 90-100: Regulator jelas, sambungan terlihat, gauge terpasang, marking ada
                                - 75-89: Regulator teridentifikasi, sebagian besar komponen terlihat
                                - 60-74: Regulator dapat dikenali, beberapa detail kurang jelas
                                - 40-59: Ada perangkat yang kemungkinan regulator tapi tidak jelas
                                - 0-39: Regulator tidak dapat diidentifikasi atau foto tidak sesuai

                                FOKUS: Regulator yang sudah terpasang dan siap beroperasi.
                                KONTEKS: Bagian dari sistem gas service yang sudah di-commission.',
                ],

                'foto_kompor_menyala' => [
                    'label' => 'Foto Kompor Menyala',
                    'accept' => ['image/*'],
                    'required' => true,
                    'prompt' => 'Analisis foto kompor menyala dengan penilaian SKOR (0-100):

                                ELEMEN YANG DIPERIKSA:
                                1. API/FLAME TERLIHAT (40 poin) - Apakah ada nyala api yang dapat dilihat?
                                2. BURNER/TUNGKU KOMPOR (30 poin) - Apakah kompor/burner terlihat jelas?
                                3. WARNA API (20 poin) - Apakah warna api dapat dinilai (biru = bagus)?
                                4. KONTEKS DAPUR (10 poin) - Apakah terlihat di lingkungan dapur/rumah?

                                PANDUAN PENILAIAN:
                                - 90-100: Api jelas menyala biru, kompor teridentifikasi, konteks jelas
                                - 75-89: Api terlihat, kompor dapat dikenali, warna cukup jelas
                                - 60-74: Ada nyala api tapi tidak terlalu jelas atau warna kurang optimal
                                - 40-59: Kompor terlihat tapi api tidak jelas atau tidak ada
                                - 0-39: Tidak ada api atau bukan foto kompor

                                FOKUS: Bukti bahwa gas sudah mengalir dan dapat digunakan untuk memasak.
                                TARGET: Api biru yang stabil menandakan gas service berhasil.
                                SAFETY: Pastikan tidak ada indikasi bahaya atau instalasi yang salah.',
                ],

            ],

            'score_messages' => [
                'excellent' => 'Foto sangat baik dan memenuhi semua kriteria Gas In',
                'good'      => 'Foto baik dan dapat diterima untuk Gas In',
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
            'foto_mgrt_url' => 'mgrt',
            'foto_pondasi_url' => 'pondasi',
            'foto_isometrik_scan_sr_url' => 'isometrik_scan',
        ],
        'GAS_IN' => [
            'ba_gas_in_url' => 'ba_gas_in',
            'foto_bubble_test_sk_url' => 'foto_bubble_test',
            'foto_regulator_url' => 'foto_regulator',
            'foto_kompor_menyala_url' => 'foto_kompor_menyala',
        ]
    ]
];
