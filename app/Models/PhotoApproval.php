<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'reff_id_pelanggan',
        'module_name',
        'photo_field_name',
        'photo_url',
        'ai_confidence_score',
        'ai_validation_result',
        'ai_approved_at',
        'tracer_user_id',
        'tracer_approved_at',
        'tracer_notes',
        'cgp_user_id',
        'cgp_approved_at',
        'cgp_notes',
        'photo_status',
        'rejection_reason'
    ];

    protected $casts = [
        'ai_validation_result' => 'array',
        'ai_approved_at' => 'datetime',
        'tracer_approved_at' => 'datetime',
        'cgp_approved_at' => 'datetime',
        'ai_confidence_score' => 'decimal:2'
    ];

    // Relationships
    public function pelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function tracerUser()
    {
        return $this->belongsTo(User::class, 'tracer_user_id');
    }

    public function cgpUser()
    {
        return $this->belongsTo(User::class, 'cgp_user_id');
    }

    // Helper Methods
    public function isAiApproved()
    {
        return in_array($this->photo_status, [
            'ai_approved', 'tracer_pending', 'tracer_approved',
            'cgp_pending', 'cgp_approved'
        ]);
    }

    public function isTracerApproved()
    {
        return in_array($this->photo_status, ['tracer_approved', 'cgp_pending', 'cgp_approved']);
    }

    public function isCgpApproved()
    {
        return $this->photo_status === 'cgp_approved';
    }

    public function needsTracerReview()
    {
        return $this->photo_status === 'tracer_pending';
    }

    public function needsCgpReview()
    {
        return $this->photo_status === 'cgp_pending';
    }

    public function isRejected()
    {
        return in_array($this->photo_status, ['ai_rejected', 'tracer_rejected', 'cgp_rejected']);
    }

    // Scopes
    public function scopePendingTracerReview($query)
    {
        return $query->where('photo_status', 'tracer_pending');
    }

    public function scopePendingCgpReview($query)
    {
        return $query->where('photo_status', 'cgp_pending');
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module_name', $module);
    }
}
