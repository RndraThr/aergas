<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoApproval extends Model
{
    use HasFactory;

    protected $table = 'photo_approvals';
    protected $guarded = [];

    protected $casts = [
        'ai_confidence_score'  => 'decimal:2',
        'ai_validation_result' => 'array',
        'ai_approved_at'       => 'datetime',
        'tracer_approved_at'   => 'datetime',
        'tracer_rejected_at'   => 'datetime',
        'cgp_approved_at'      => 'datetime',
        'uploaded_at'          => 'datetime',
        'organized_at'         => 'datetime',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'photo_status'         => 'string',
        'module_name'          => 'string',
        'ai_checks'            => 'array',
        'ai_score'             => 'decimal:2',
    ];

    // --------- Relations ---------
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function tracerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tracer_user_id');
    }

    public function cgpUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cgp_user_id');
    }

    public function jalurLowering(): BelongsTo
    {
        return $this->belongsTo(JalurLoweringData::class, 'module_record_id');
    }

    public function jalurJoint(): BelongsTo
    {
        return $this->belongsTo(JalurJointData::class, 'module_record_id');
    }

    // --------- Scopes ---------
    public function scopeByModule($q, string $module) { return $q->where('module_name', $module); }
    public function scopePendingTracer($q) { return $q->where('photo_status', 'tracer_pending'); }
    public function scopePendingCgp($q)    { return $q->where('photo_status', 'cgp_pending'); }

    // --------- Helpers ---------
    public function isAiApproved(): bool
    {
        return in_array($this->photo_status, ['tracer_pending','tracer_approved','cgp_pending','cgp_approved'], true);
    }

    public function isTracerApproved(): bool
    {
        return in_array($this->photo_status, ['tracer_approved','cgp_pending','cgp_approved'], true);
    }

    public function isCgpApproved(): bool
    {
        return $this->photo_status === 'cgp_approved';
    }
}
