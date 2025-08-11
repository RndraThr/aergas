<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GudangStockBalance extends Model
{
    public $timestamps = false;
    protected $table = 'gudang_stock_balances';
    protected $fillable = [];
    protected $casts = ['on_hand' => 'decimal:3'];

    public function item() { return $this->belongsTo(GudangItem::class, 'gudang_item_id'); }
}
