<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JalurTestPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'flushing_date' => 'date',
        'pneumatic_date' => 'date',
        'purging_date' => 'date',
        'gas_in_date' => 'date',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(JalurCluster::class, 'cluster_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JalurTestPackageItem::class, 'test_package_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
