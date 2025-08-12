<?php
// config/aergas_photos.php

return [

    // Dipakai FileUploadService untuk penamaan file
    'naming' => [
        // opsi: reff_slot_ts | reff_slot | ts_reff_slot_orig
        'pattern' => env('AERGAS_PHOTO_NAMING', 'reff_slot_ts'),
    ],

    // Batas file upload
    'limits' => [
        'max_bytes' => (int) env('PHOTO_MAX_SIZE_BYTES', env('MAX_FILE_SIZE', 10240) * 1024), // bytes
        'allowed_mime_types' => ['image/jpeg','image/png','image/jpg','image/webp','application/pdf'],
    ],

    // FE alias → key kanonik slot
    'aliases' => [
        'SK' => [
            'foto_pneumatic_start_sk_url'  => 'pneumatic_start',
            'foto_pneumatic_finish_sk_url' => 'pneumatic_finish',
            'foto_valve_sk_url'            => 'valve',
            'foto_pipa_depan_sk_url'       => 'pipa_depan',
            'scan_isometrik_sk_url'        => 'isometrik_scan',
        ],
        'SR' => [
            'foto_pneumatic_start_sr_url'  => 'pneumatic_start',
            'foto_pneumatic_finish_sr_url' => 'pneumatic_finish',
            'foto_kedalaman_sr_url'        => 'kedalaman',
            'scan_isometrik_sr_url'        => 'isometrik_scan',
            'foto_jenis_tapping_sr_url'    => 'tapping_saddle',
        ],
    ],

    'modules' => [

        // ====================== SK ======================
        'SK' => [
            'min_required_slots' => 5,
            'replace_same_slot'  => true,

            'slots' => [
                // 1) START
                'pneumatic_start' => [
                    'label'  => 'Foto Pneumatic START SK',
                    'accept' => ['image/*'],

                    // cek yang dipakai evaluator
                    'checks' => [
                        'not_blurry' => [
                            'label'          => 'Foto tidak blur dan jelas',
                            'hint'           => 'Teks/angka di papan atau label masih terbaca.',
                            'min_confidence' => 0.65, // ≥ 0.65 = lulus
                            'warn_min'       => 0.50, // 0.50–0.64 = lulus (peringatan)
                        ],
                    ],

                    // prompt-mode (aktif = true)
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Tugas Anda menilai satu foto instalasi "Pneumatic START".
Kriteria:
1) not_blurry: Foto tidak buram. Jika teks/angka pada papan atau label masih cukup terbaca, anggap LULUS. Toleransi ringan untuk noise.
Kembalikan JSON ketat:
{
  "criteria": [
    {"id":"not_blurry","passed":true|false,"confidence":0..1,"reason":"singkat"},
  ],
  "overall_passed": true|false,
  "notes": ["catatan singkat", ...]
}
JANGAN ada teks lain di luar JSON.
PROMPT,
                ],

                // 2) FINISH
                'pneumatic_finish' => [
                    'label'  => 'Foto Pneumatic FINISH SK',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.65,
                            'warn_min'       => 0.50,
                        ],
                        'pipe_connected' => [
                            'label' => 'Pipa tersambung',
                            'min_confidence' => 0.55,
                            'warn_min'       => 0.40,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Pneumatic FINISH".
Kriteria:
1) not_blurry: Foto cukup jelas; jika sebagian kecil kurang tajam namun teks/objek utama bisa dikenali, tetap LULUS.
2) pipe_connected: Pastikan pipa & sambungan tampak terpasang/tersambung dengan benar (bukan terlepas).

Output JSON-only:
{
  "criteria":[
    {"id":"not_blurry","passed":true|false,"confidence":0..1,"reason":"singkat"},
    {"id":"pipe_connected","passed":true|false,"confidence":0..1,"reason":"singkat"}
  ],
  "overall_passed": true|false,
  "notes":[]
}
PROMPT,
                ],

                // 3) VALVE
                'valve' => [
                    'label'  => 'Foto Valve SK',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Valve".
Kriteria:
1) not_blurry: Foto cukup jelas; bentuk valve dan detail utama terlihat. Jika sedikit noise tapi masih terbaca, LULUS.

Balas JSON:
{
  "criteria":[
    {"id":"not_blurry","passed":true|false,"confidence":0..1,"reason":"singkat"}
  ],
  "overall_passed": true|false,
  "notes":[]
}
PROMPT,
                ],

                // 4) PIPA DEPAN
                'pipa_depan' => [
                    'label'  => 'Foto Pipa SK Depan (tampak luar)',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                        'pipe_connected' => [
                            'label' => 'Pipa tersambung',
                            'min_confidence' => 0.55,
                            'warn_min'       => 0.40,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Pipa Depan (tampak luar)".
Kriteria:
1) not_blurry: Foto cukup jelas (tidak buram parah).
2) pipe_connected: Ada continuity pipa/sambungan tampak utuh.

JSON ONLY:
{
  "criteria":[
    {"id":"not_blurry","passed":true|false,"confidence":0..1,"reason":"singkat"},
    {"id":"pipe_connected","passed":true|false,"confidence":0..1,"reason":"singkat"}
  ],
  "overall_passed": true|false,
  "notes":[]
}
PROMPT,
                ],

                // 5) ISOMETRIK (boleh PDF)
                'isometrik_scan' => [
                    'label'  => 'Scan Isometrik SK (TTD lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'checks' => [
                        'has_all_signatures' => [
                            'label' => 'Tiga tanda tangan lengkap',
                            'hint'  => 'Semua kolom TTD terisi (bukan kosong).',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai scan "Isometrik SK".
Kriteria:
1) has_all_signatures: Pastikan tanda tangan 3 pihak lengkap/terisi pada dokumen (bukan kosong). Jika hanya 2 atau kurang, gagal.

Balas JSON:
{
  "criteria":[
    {"id":"has_all_signatures","passed":true|false,"confidence":0..1,"reason":"singkat"}
  ],
  "overall_passed": true|false,
  "notes":[]
}
PROMPT,
                ],
            ],
        ],

        // ====================== SR ======================
        'SR' => [
            'min_required_slots' => 5,
            'replace_same_slot'  => true,

            'slots' => [
                'pneumatic_start' => [
                    'label'  => 'Foto Pneumatic START SR',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.65,
                            'warn_min'       => 0.50,
                        ],
                        'pipe_connected' => [
                            'label' => 'Pipa tersambung',
                            'min_confidence' => 0.55,
                            'warn_min'       => 0.40,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Pneumatic START SR".
Kriteria:
1) not_blurry: jika teks/angka pada papan/label masih bisa dibaca, LULUS.
2) pipe_connected: pipa/sambungan tampak terpasang.

JSON ONLY dengan field id seperti di atas.
PROMPT,
                ],

                'pneumatic_finish' => [
                    'label'  => 'Foto Pneumatic FINISH SR',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.65,
                            'warn_min'       => 0.50,
                        ],
                        'pipe_connected' => [
                            'label' => 'Pipa tersambung',
                            'min_confidence' => 0.55,
                            'warn_min'       => 0.40,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Pneumatic FINISH SR".
Kriteria:
1) not_blurry: foto cukup jelas.
2) pipe_connected: pipa & sambungan tampak terpasang benar.

Balas JSON-only sesuai format kriteria.
PROMPT,
                ],

                'tapping_saddle' => [
                    'label'    => 'Foto Jenis Tapping (Saddle)',
                    'accept'   => ['image/*'],
                    'requires' => ['fields' => ['jenis_tapping']], // wajib isi field manual dulu
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Jenis Tapping (Saddle)".
Kriteria:
1) not_blurry: foto cukup jelas.

JSON-only:
{
  "criteria":[{"id":"not_blurry","passed":true|false,"confidence":0..1,"reason":"singkat"}],
  "overall_passed": true|false,
  "notes":[]
}
PROMPT,
                ],

                'kedalaman' => [
                    'label'  => 'Foto Kedalaman Galian',
                    'accept' => ['image/*'],
                    'checks' => [
                        'not_blurry' => [
                            'label' => 'Foto tidak blur',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai foto "Kedalaman Galian".
Kriteria:
1) not_blurry: foto cukup jelas. (Jika terlihat alat ukur/pita ukur makin baik, namun fokus utama tetap kejernihan foto.)

JSON-only sesuai format kriteria di atas.
PROMPT,
                ],

                'isometrik_scan' => [
                    'label'  => 'Scan Isometrik SR (TTD lengkap)',
                    'accept' => ['image/*', 'application/pdf'],
                    'checks' => [
                        'has_all_signatures' => [
                            'label' => 'Tanda tangan lengkap',
                            'min_confidence' => 0.60,
                            'warn_min'       => 0.45,
                        ],
                    ],
                    'prompt_mode' => true,
                    'prompt' => <<<PROMPT
Nilai scan "Isometrik SR".
Kriteria:
1) has_all_signatures: cek kolom tanda tangan lengkap (bukan kosong).

Kembalikan JSON-only sesuai format.
PROMPT,
                ],
            ],

            // FIELD MANUAL (disimpan di sr_data)
            'fields' => [
                'jenis_tapping' => [
                    'label'   => 'Jenis Tapping',
                    'type'    => 'enum',
                    'options' => ['63x20','90x20','63x32','180x90','180x63','125x63','90x63','180x32','125x32','90x32'],
                    'required'=> true,
                ],
                'panjang_pipa_pe_m' => [
                    'label'    => 'Panjang pipa PE (meter)',
                    'type'     => 'number', 'step'=>0.01, 'min'=>0, 'required'=> true,
                ],
                'panjang_casing_crossing_m' => [
                    'label'    => 'Panjang casing crossing SR (meter)',
                    'type'     => 'number', 'step'=>0.01, 'min'=>0, 'required'=> false,
                ],
            ],
        ],
    ],
];
