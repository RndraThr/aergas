<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class BaseModuleModel extends Model
{
    use HasFactory;

    // Common approval fields yang ada di semua module
    protected $approvalCasts = [
        'tracer_approved_at' => 'datetime',
        'cgp_approved_at' => 'datetime',
    ];

    // Abstract methods yang harus diimplementasi child class
    abstract public function getRequiredPhotos(): array;
    abstract public function getModuleName(): string;

    public function getCasts()
    {
        return array_merge([
            'tracer_approved_at' => 'datetime',
            'cgp_approved_at' => 'datetime',
        ], parent::getCasts());
    }
    // Common relationships
    public function pelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function tracerApprover()
    {
        return $this->belongsTo(User::class, 'tracer_approved_by');
    }

    public function cgpApprover()
    {
        return $this->belongsTo(User::class, 'cgp_approved_by');
    }

    public function photoApprovals()
    {
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
                    ->where('module_name', $this->getModuleName());
    }

    public function fileStorages()
    {
        return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
                    ->where('module_name', $this->getModuleName());
    }

    // Common helper methods
    public function isCompleted()
    {
        return $this->module_status === 'completed';
    }

    public function isInProgress()
    {
        return in_array($this->module_status, ['draft', 'ai_validation', 'tracer_review', 'cgp_review']);
    }

    public function canSubmit()
    {
        $requiredPhotos = $this->getRequiredPhotos();

        // Check all required photos are uploaded
        foreach ($requiredPhotos as $photoField) {
            if (empty($this->$photoField)) {
                return false;
            }
        }

        // Check all photos are AI approved minimum
        $pendingPhotos = $this->photoApprovals()
                              ->whereNotIn('photo_status', ['ai_approved', 'tracer_approved', 'cgp_approved'])
                              ->count();

        return $pendingPhotos === 0;
    }

    public function getAllPhotosStatus()
    {
        $requiredPhotos = $this->getRequiredPhotos();
        $photoStatuses = [];

        foreach ($requiredPhotos as $photoField) {
            $approval = $this->photoApprovals()
                           ->where('photo_field_name', $photoField)
                           ->first();

            $photoStatuses[$photoField] = [
                'url' => $this->$photoField,
                'status' => $approval ? $approval->photo_status : 'draft',
                'approval' => $approval
            ];
        }

        return $photoStatuses;
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('module_status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('module_status', ['draft', 'ai_validation', 'tracer_review', 'cgp_review']);
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('module_status', ['tracer_review', 'cgp_review']);
    }
}
