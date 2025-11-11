<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsePhoto extends Model
{
    protected $fillable = [
        'daily_report_id',
        'photo_category',
        'photo_url',
        'drive_file_id',
        'drive_link',
        'storage_path',
        'storage_disk',
        'keterangan',
        'uploaded_by',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(HseDailyReport::class, 'daily_report_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getCategoryLabel(): string
    {
        return match($this->photo_category) {
            'pekerjaan' => 'Pekerjaan',
            'tbm' => 'Toolbox Meeting',
            'kondisi_site' => 'Kondisi Site',
            'apd' => 'APD',
            'housekeeping' => 'Housekeeping',
            'incident' => 'Incident',
            default => 'Lainnya',
        };
    }

    public function isFromDrive(): bool
    {
        return $this->storage_disk === 'gdrive' && !empty($this->drive_file_id);
    }

    /**
     * Get the direct image URL for displaying in browser
     * Converts Google Drive webViewLink to direct image URL
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->isFromDrive()) {
            // Convert Google Drive webViewLink to direct image URL
            // Format: https://drive.google.com/thumbnail?id={fileId}&sz=w1000
            return "https://drive.google.com/thumbnail?id={$this->drive_file_id}&sz=w1000";
        }

        return $this->photo_url;
    }

    /**
     * Get thumbnail URL for smaller previews
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->isFromDrive()) {
            return "https://drive.google.com/thumbnail?id={$this->drive_file_id}&sz=w400";
        }

        return $this->photo_url;
    }
}
