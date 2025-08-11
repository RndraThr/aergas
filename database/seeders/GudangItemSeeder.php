<?php

namespace Database\Seeders;

// database/seeders/GudangItemSeeder.php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class extends Seeder {
    public function run(): void {
        $items = [
            // MATERIAL SR - POSTPAID FIM
            ['code'=>'SR-TSD63','name'=>'Tapping Saddle 63x20mm','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-CPL20','name'=>'Coupler 20mm','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-PIP20','name'=>'Pipa PE 20 mm','unit'=>'meter','category'=>'SR_FIM'],
            ['code'=>'SR-ELB20','name'=>'Elbow, PE 20 mm','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-FTF20','name'=>'Female Transition Fitting PE 20 mm','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-PGT4','name'=>'Pipa Galvanize 3/4\" (Tiang Meter Gas)','unit'=>'meter','category'=>'SR_FIM'],
            ['code'=>'SR-KLM4','name'=>'Klem Pipa 3/4\"','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-BAL4','name'=>'Ball Valves 3/4\"','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-LEL4','name'=>'Long Elbow 90° 3/4\"','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-DNG4','name'=>'Double Nipple galvanize 3/4\"','unit'=>'Buah','category'=>'SR_FIM'],
            ['code'=>'SR-REG','name'=>'Regulator','unit'=>'Buah','category'=>'SR_FIM'],

            // MATERIAL SK FIM
            ['code'=>'SK-ELB2','name'=>'Elbow 3/4\" to 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-DNG2','name'=>'Double Nipple galvanize 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-PIP2','name'=>'Pipa Galvanize 1/2\"','unit'=>'meter','category'=>'SK_FIM'],
            ['code'=>'SK-ELB2-12','name'=>'Elbow 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-BAL2','name'=>'Ball Valve 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-NIP2','name'=>'Nipple Slang 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-KLM2','name'=>'Klem Pipa 1/2\"','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-SDT2','name'=>'SockDraft, Galvanis Dia 1/2 inch','unit'=>'Buah','category'=>'SK_FIM'],
            ['code'=>'SK-SEL','name'=>'Sealtape','unit'=>'roll','category'=>'SK_FIM'],

            // MATERIAL KSM
            ['code'=>'KSM-PIP180','name'=>'Pipa PE Ø 180 mm, HDPE 100, YELLOW, ISO 4437 SDR 13,6','unit'=>'meter','category'=>'KSM'],
            ['code'=>'KSM-ELB90-180','name'=>'Elbow 90 Ø 180mm PE 80 / PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-ELB45-180','name'=>'Elbow 45, Ø 180mm PE 80 / PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-COU180','name'=>'Coupler Electrofusion 180 mm, PE 80/PE 100 SDR 13,6','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-END180','name'=>'End Cap Ø 180 mm PE 80 / PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-RED180-63','name'=>'Reducer 180 mm x 63 mm, PE 80 / PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-TEE180-80','name'=>'Tee Equal 180 mm PE 80/PE 100 SDR 11 ISO 4437 Ø 180 x 180 x 180 mm','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-PIP63','name'=>'Pipa PE Ø 63 mm, HDPE 100, YELLOW, ISO 4437 SDR 13,6','unit'=>'meter','category'=>'KSM'],
            ['code'=>'KSM-ELB90-63','name'=>'Elbow 90, Ø 63 mm PE 80/PE 100 SDR 11 (Electrofusion)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-ELB45-63','name'=>'Elbow 45, Ø 63 mm PE 80/PE 100 SDR 11 (Electrofusion)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-COU63','name'=>'Coupler Electrofusion Ø 63 mm, PE 100 SDR 13,6 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-TEE63','name'=>'Tee Equal Electrofusion Ø 63, PE 80/PE 100 SDR 11 ISO 4437 (Ø63xØ63xØ63)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-END63','name'=>'End Cap Electrofusion Pipa Ø 63 mm, PE 80/PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-FLA63','name'=>'Flange Adaptor MDPE, SDR 11, YELLOW 2\" x 63mm','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-BAL63','name'=>'Ball Valve Ø 63 mm, ISO 4437-4, MDPE 80/HDPE 100 (Extension Stem)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-CAS4-63','name'=>'Pipa Casing 4\" Galvanis - Untuk Pipa PE 63mm','unit'=>'meter','category'=>'KSM'],
            ['code'=>'KSM-CAS1-20','name'=>'Pipa Casing 1\" Galvanis - Untuk Pipa PE 20mm','unit'=>'meter','category'=>'KSM'],
            ['code'=>'KSM-MGRT','name'=>'Meter Rumah Tangga (Meter RT), G 1.6 Inlet/Outlet size 3/4\" (Include Coupling Meter)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-TRF32','name'=>'Transition Fitting 32 mm x 1\" (khusus pipa servis 32 & 63 mm)','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-MRS','name'=>'MRS P3 G6 Class #150 1/1-4/2-1/0.3-20/15-G6 Konfigurasi E1','unit'=>'Unit','category'=>'KSM'],
            ['code'=>'KSM-EVC','name'=>'EVC (Electronic Volume Corrector) LOW','unit'=>'Unit','category'=>'KSM'],
            ['code'=>'KSM-ELB90-32','name'=>'Elbow 90 Dia. 32mm SDR11','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-COU32','name'=>'Coupler Electrofusion Ø 32 mm, PE 100 SDR 13,6 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-RED63-32','name'=>'Reducer 63 mm x 32 mm, PE 80 / PE 100 SDR 11 ISO 4437','unit'=>'Buah','category'=>'KSM'],
            ['code'=>'KSM-SK-SEL','name'=>'Sealtape','unit'=>'roll','category'=>'KSM'],
        ];

        DB::table('gudang_items')->upsert($items, ['code'], ['name','unit','category','is_active','meta','updated_at']);
    }
};
