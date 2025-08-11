<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestItem extends Model
{
    protected $table = 'material_request_items';

    protected $fillable = [
        'material_request_id','gudang_item_id','unit',
        'qty_requested','qty_approved','qty_issued',
        'qty_installed','qty_returned','qty_reject',
        'notes',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:3',
        'qty_approved'  => 'decimal:3',
        'qty_issued'    => 'decimal:3',
        'qty_installed' => 'decimal:3',
        'qty_returned'  => 'decimal:3',
        'qty_reject'    => 'decimal:3',
    ];

    /* RELATIONS */
    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GudangItem::class, 'gudang_item_id');
    }

    /* HELPERS */
    public function getRemainingToIssueAttribute(): float
    {
        $rem = (float)$this->qty_approved - (float)$this->qty_issued;
        return max(0.0, round($rem, 3));
    }

    public function applyIssue(float $qty): void
    {
        $this->qty_issued = round(((float)$this->qty_issued + $qty), 3);
        $this->save();
    }
    public function applyInstall(float $qty): void
    {
        $this->qty_installed = round(((float)$this->qty_installed + $qty), 3);
        $this->save();
    }
    public function applyReturn(float $qty): void
    {
        $this->qty_returned = round(((float)$this->qty_returned + $qty), 3);
        $this->save();
    }
    public function applyReject(float $qty): void
    {
        $this->qty_reject = round(((float)$this->qty_reject + $qty), 3);
        $this->save();
    }
}
