<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsePekerjaanHarian extends Model
{
    protected $table = 'hse_pekerjaan_harian';

    protected $fillable = [
        'daily_report_id',
        'jenis_pekerjaan',
        'deskripsi_pekerjaan',
        'lokasi_detail',
        'google_maps_link',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(HseDailyReport::class, 'daily_report_id');
    }

    public function hasGoogleMapsLink(): bool
    {
        return !empty($this->google_maps_link);
    }

    public function getGoogleMapsUrl(): ?string
    {
        return $this->google_maps_link;
    }
}
