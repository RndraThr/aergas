<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileStorage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reff_id_pelanggan',
        'module_name',
        'field_name',
        'original_filename',
        'stored_filename',
        'file_path',
        'google_drive_id',
        'mime_type',
        'file_size',
        'file_hash',
        'uploaded_by'
    ];

    // di FileStorage
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function pelanggan() { return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan'); }

    public function scopeByReff($q, $reff) { return $q->where('reff_id_pelanggan', $reff); }
    public function scopeActive($q) { return $q->where('status','active'); }

    public function uploadedByUser()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper methods
    public function getFileSizeHuman()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage()
    {
        return strpos($this->mime_type, 'image/') === 0;
    }

    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    // Scopes
    public function scopeByModule($query, $module)
    {
        return $query->where('module_name', $module);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }
}
