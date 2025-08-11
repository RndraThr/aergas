<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GudangItem extends Model
{
    protected $table = 'gudang_items';

    protected $fillable = ['code','name','unit','category','is_active','meta'];
    protected $casts = ['is_active' => 'boolean', 'meta' => 'array'];

    public const CAT_SR_FIM = 'SR_FIM';
    public const CAT_SK_FIM = 'SK_FIM';
    public const CAT_KSM    = 'KSM';

    // RELATIONS
    public function transactions()
    {
        return $this->hasMany(GudangTransaction::class, 'gudang_item_id');
    }

    public function stockBalance()
    {
        return $this->hasOne(GudangStockBalance::class, 'gudang_item_id');
    }

    // SCOPES
    public function scopeActive($q)   { return $q->where('is_active', true); }
    public function scopeCategory($q, string $cat) { return $q->where('category', $cat); }

    // HELPERS
    public function getOnHandAttribute(): float
    {
        // Coba pakai VIEW jika ada, kalau tidak fallback hitung dari transaksi
        try {
            if (Schema::hasTable('gudang_stock_balances')) {
                $val = DB::table('gudang_stock_balances')
                    ->where('gudang_item_id', $this->id)
                    ->value('on_hand');

                if ($val !== null) {
                    return (float) $val;
                }
            }
        } catch (\Throwable $e) {
            // ignore & fallback
        }

        $in  = (float) $this->transactions()->whereIn('type', ['IN','RETURN','ADJUST'])->sum('qty');
        $out = (float) $this->transactions()->whereIn('type', ['OUT','REJECT','INSTALLED'])->sum('qty');
        return round($in - $out, 3);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CAT_SR_FIM => 'Material SR - FIM',
            self::CAT_SK_FIM => 'Material SK - FIM',
            self::CAT_KSM    => 'Material KSM',
            default          => $this->category,
        };
    }
}
