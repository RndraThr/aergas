<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HseToolboxMeeting extends Model
{
    protected $fillable = [
        'daily_report_id',
        'tanggal_tbm',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'jumlah_peserta',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_tbm' => 'date',
        'jumlah_peserta' => 'integer',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(HseDailyReport::class, 'daily_report_id');
    }

    public function materiList(): HasMany
    {
        return $this->hasMany(HseTbmMateri::class, 'toolbox_meeting_id')->orderBy('urutan');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
