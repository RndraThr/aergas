<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pilot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Basic Info
        'nama', 'nomor_kartu_identitas', 'nomor_ponsel', 'alamat',
        'rt', 'rw', 'id_kota_kab', 'id_kecamatan', 'id_kelurahan', 'padukuhan',

        // ID & Status
        'id_reff', 'penetrasi_pengembangan',

        // Tanggal Pemasangan
        'tanggal_terpasang_sk', 'tanggal_terpasang_sr', 'tanggal_terpasang_gas_in',

        // Keterangan & Status
        'keterangan', 'batal', 'keterangan_batal', 'anomali',

        // Material SK (9 items)
        'mat_sk_elbow_3_4_to_1_2', 'mat_sk_double_nipple_1_2',
        'mat_sk_pipa_galvanize_1_2', 'mat_sk_elbow_1_2', 'mat_sk_ball_valve_1_2',
        'mat_sk_nipple_slang_1_2', 'mat_sk_klem_pipa_1_2', 'mat_sk_sockdraft_galvanis_1_2',
        'mat_sk_sealtape',

        // Material SR (15 items)
        'mat_sr_ts_63x20mm', 'mat_sr_coupler_20mm', 'mat_sr_pipa_pe_20mm',
        'mat_sr_elbow_pe_20mm', 'mat_sr_female_tf_pe_20mm', 'mat_sr_pipa_galvanize_3_4',
        'mat_sr_klem_pipa_3_4', 'mat_sr_ball_valves_3_4', 'mat_sr_long_elbow_90_3_4',
        'mat_sr_double_nipple_3_4', 'mat_sr_regulator', 'mat_sr_meter_gas_rumah_tangga',
        'mat_sr_cassing_1', 'mat_sr_coupling_mgrt', 'mat_sr_sealtape',

        // Evidence SK (5 photos)
        'ev_sk_foto_berita_acara_pemasangan', 'ev_sk_foto_pneumatik_start',
        'ev_sk_foto_pneumatik_finish', 'ev_sk_foto_valve_sk', 'ev_sk_foto_isometrik_sk',

        // Evidence SR (6 photos)
        'ev_sr_foto_pneumatik_start', 'ev_sr_foto_pneumatik_finish',
        'ev_sr_foto_jenis_tapping', 'ev_sr_foto_kedalaman',
        'ev_sr_foto_cassing', 'ev_sr_foto_isometrik_sr',

        // Evidence MGRT (3 items)
        'ev_mgrt_foto_meter_gas_rumah_tangga', 'ev_mgrt_foto_pondasi_mgrt',
        'ev_mgrt_nomor_seri_mgrt',

        // Evidence Gas In (7 items)
        'ev_gasin_berita_acara_gas_in', 'ev_gasin_rangkaian_meter_gas_pondasi',
        'ev_gasin_foto_bubble_test', 'ev_gasin_foto_mgrt',
        'ev_gasin_foto_kompor_menyala_pelanggan', 'ev_gasin_foto_stiker_sosialisasi',
        'ev_gasin_nomor_seri_mgrt',

        // Review CGP (3 items)
        'review_cgp_sk', 'review_cgp_sr', 'review_cgp_gas_in',

        // Dokumen (4 items)
        'ba_gas_in', 'asbuilt_sk', 'asbuilt_sr', 'comment_cgp',

        // Metadata
        'batch_id', 'uploaded_by',
    ];

    protected $casts = [
        'tanggal_terpasang_sk' => 'date',
        'tanggal_terpasang_sr' => 'date',
        'tanggal_terpasang_gas_in' => 'date',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
