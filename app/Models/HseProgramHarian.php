<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HseProgramHarian extends Model
{
    protected $table = 'hse_program_harian';

    protected $fillable = [
        'daily_report_id',
        'nama_program',
        'keterangan',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(HseDailyReport::class, 'daily_report_id');
    }
}
