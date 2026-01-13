<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JalurTestPackageItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function testPackage(): BelongsTo
    {
        return $this->belongsTo(JalurTestPackage::class, 'test_package_id');
    }

    public function lineNumber(): BelongsTo
    {
        return $this->belongsTo(JalurLineNumber::class, 'line_number_id');
    }
}
