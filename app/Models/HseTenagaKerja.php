<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HseTenagaKerja extends Model
{
    protected $table = 'hse_tenaga_kerja';

    protected $fillable = [
        'daily_report_id',
        'kategori_team',
        'role_name',
        'jumlah_orang',
    ];

    protected $casts = [
        'jumlah_orang' => 'integer',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(HseDailyReport::class, 'daily_report_id');
    }
}
