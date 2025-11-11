<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HseTbmMateri extends Model
{
    protected $table = 'hse_tbm_materi';

    protected $fillable = [
        'toolbox_meeting_id',
        'urutan',
        'materi_pembahasan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function toolboxMeeting(): BelongsTo
    {
        return $this->belongsTo(HseToolboxMeeting::class, 'toolbox_meeting_id');
    }
}
