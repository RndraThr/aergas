<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class BaBatalData extends Model
{
    use HasFactory;

    protected $table = 'ba_batal_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'alasan_batal',
        'foto_ba_url',
        'foto_pelanggan_url',
        'tanggal_pembatalan',
        'processed_by'
    ];

    protected $casts = [
        'tanggal_pembatalan' => 'datetime',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function processedByUser()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
