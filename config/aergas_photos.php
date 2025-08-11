<?php
// config/aergas_photos.php

return [

    // FE → key kanonik (biar nama lama tetap diterima)
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
            'slots' => [
                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SK',
                    'required_objects' => ['pneumatic','pressure_gauge','hose'],
                    'min_resolution'   => [800, 600],
                    'accept' => ['image/*'],
                ],
                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SK',
                    'required_objects' => ['pneumatic','pressure_gauge'],
                    'min_resolution'   => [800, 600],
                    'accept' => ['image/*'],
                ],
                'valve' => [
                    'label' => 'Foto Valve SK',
                    'required_objects' => ['gas_valve','seal'],
                    'accept' => ['image/*'],
                ],
                'pipa_depan' => [
                    'label' => 'Foto Pipa SK Depan (tampak luar)',
                    'required_objects' => ['pipe'],
                    'accept' => ['image/*'],
                ],
                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SK (TTD lengkap)',
                    'required_objects' => ['document','signature'],
                    'accept' => ['image/*','application/pdf'],
                ],
            ],
        ],

        // ====================== SR ======================
        'SR' => [
            // 5 foto diwajibkan: start, finish, tapping, kedalaman, isometrik
            'min_required_slots' => 5,

            // Slot FOTO (divalidasi AI)
            'slots' => [
                'pneumatic_start' => [
                    'label' => 'Foto Pneumatic START SR',
                    'required_objects' => ['pneumatic','pressure_gauge','hose'],
                    'min_resolution'   => [800, 600],
                    'accept' => ['image/*'],
                ],
                'pneumatic_finish' => [
                    'label' => 'Foto Pneumatic FINISH SR',
                    'required_objects' => ['pneumatic','pressure_gauge'],
                    'min_resolution'   => [800, 600],
                    'accept' => ['image/*'],
                ],
                'tapping_saddle' => [
                    'label' => 'Foto Jenis Tapping (Saddle)',
                    'required_objects' => ['tapping_saddle'], // ganti sesuai label deteksi AI-mu
                    'accept' => ['image/*'],
                    'requires' => ['fields' => ['jenis_tapping']], // WAJIB pilih dulu
                ],
                'kedalaman' => [
                    'label' => 'Foto Kedalaman Galian',
                    'required_objects' => ['measuring_tape','trench'], // bisa 'ruler' sesuai AI
                    'accept' => ['image/*'],
                ],
                'isometrik_scan' => [
                    'label' => 'Scan Isometrik SR (TTD lengkap)',
                    'required_objects' => ['document','signature'],
                    'accept' => ['image/*','application/pdf'],
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
