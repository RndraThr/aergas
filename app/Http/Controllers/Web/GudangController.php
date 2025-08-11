<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\GudangItem;
use App\Models\GudangTransaction;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\SkData;
use App\Models\SrData;

class GudangController extends Controller
{
    /* ==================== ITEMS ==================== */

    // GET /gudang/items
    public function items(Request $r)
    {
        $q = GudangItem::query()
            ->when($r->category, fn($qq) => $qq->where('category', $r->category))
            ->when(isset($r->active), fn($qq) => $qq->where('is_active', (bool)$r->active))
            ->when($r->q, function ($qq) use ($r) {
                $s = trim((string)$r->q);
                $qq->where(function ($w) use ($s) {
                    $w->where('code','like',"%$s%")
                      ->orWhere('name','like',"%$s%");
                });
            })
            ->orderBy('code');

        // Join stok on-hand dari view
        $q->leftJoin('gudang_stock_balances as gsb', 'gsb.gudang_item_id', '=', 'gudang_items.id')
          ->addSelect('gudang_items.*')
          ->addSelect(DB::raw('COALESCE(gsb.on_hand,0) as on_hand'));

        return response()->json($q->paginate((int)$r->get('per_page', 50)));
    }

    // POST /gudang/items
    public function itemStore(Request $r)
    {
        $v = Validator::make($r->all(), [
            'code' => ['required','string','max:50','unique:gudang_items,code'],
            'name' => ['required','string','max:255'],
            'unit' => ['nullable','string','max:50'],
            'category' => ['required', Rule::in(['SR_FIM','SK_FIM','KSM'])],
            'is_active' => ['boolean'],
            'meta' => ['array'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);
        $item = GudangItem::create($v->validated());
        return response()->json($item, 201);
    }

    // PUT /gudang/items/{item}
    public function itemUpdate(Request $r, GudangItem $item)
    {
        $v = Validator::make($r->all(), [
            'name' => ['sometimes','string','max:255'],
            'unit' => ['sometimes','nullable','string','max:50'],
            'category' => ['sometimes', Rule::in(['SR_FIM','SK_FIM','KSM'])],
            'is_active' => ['sometimes','boolean'],
            'meta' => ['sometimes','array'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);
        $item->fill($v->validated());
        $item->save();
        return response()->json($item);
    }

    // POST /gudang/items/{item}/toggle
    public function itemToggle(GudangItem $item)
    {
        $item->is_active = !$item->is_active;
        $item->save();
        return response()->json($item);
    }

    /* ==================== STOCK ==================== */

    // GET /gudang/stock
    public function stock(Request $r)
    {
        $q = GudangItem::query()
            ->leftJoin('gudang_stock_balances as gsb', 'gsb.gudang_item_id', '=', 'gudang_items.id')
            ->addSelect('gudang_items.*')
            ->addSelect(DB::raw('COALESCE(gsb.on_hand,0) as on_hand'))
            ->when($r->category, fn($qq) => $qq->where('category', $r->category))
            ->orderBy('code');

        return response()->json($q->paginate((int)$r->get('per_page', 100)));
    }

    /* ==================== TRANSACTIONS ==================== */

    // GET /gudang/transactions
    public function transactions(Request $r)
    {
        $q = GudangTransaction::query()
            ->with(['item'])
            ->when($r->type, fn($qq) => $qq->whereIn('type', (array)$r->type))
            ->when($r->gudang_item_id, fn($qq) => $qq->where('gudang_item_id', $r->gudang_item_id))
            ->when($r->code, fn($qq) => $qq->whereHas('item', fn($w) => $w->where('code',$r->code)))
            ->when($r->date_from && $r->date_to, fn($qq) => $qq->whereBetween('transacted_at', [$r->date_from, $r->date_to]))
            ->orderByDesc('transacted_at');

        return response()->json($q->paginate((int)$r->get('per_page', 50)));
    }

    // POST /gudang/transactions (generic)
    public function txStore(Request $r)
    {
        $v = Validator::make($r->all(), [
            'gudang_item_id' => ['required','integer','exists:gudang_items,id'],
            'type' => ['required', Rule::in(['IN','OUT','RETURN','REJECT','INSTALLED','ADJUST'])],
            'qty'  => ['required','numeric','min:0.001'],
            'unit' => ['nullable','string','max:50'],
            'ref_no' => ['nullable','string','max:100'],
            'notes' => ['nullable','string'],
            'sourceable_type' => ['nullable','string','max:255'],
            'sourceable_id' => ['nullable','integer'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $data = $v->validated();

        // Cek stok jika transaksi keluar
        if (in_array($data['type'], ['OUT','REJECT','INSTALLED'])) {
            $onHand = $this->onHand((int)$data['gudang_item_id']);
            if ($data['qty'] > $onHand + 1e-9) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi',
                    'on_hand' => $onHand,
                ], 422);
            }
        }

        $data['created_by'] = Auth::id();
        $tx = GudangTransaction::create($data);
        return response()->json($tx, 201);
    }

    // Helper endpoints untuk tipe spesifik
    public function txIn(Request $r)       { $r->merge(['type'=>'IN']);        return $this->txStore($r); }
    public function txOut(Request $r)      { $r->merge(['type'=>'OUT']);       return $this->txStore($r); }
    public function txReturn(Request $r)   { $r->merge(['type'=>'RETURN']);    return $this->txStore($r); }
    public function txReject(Request $r)   { $r->merge(['type'=>'REJECT']);    return $this->txStore($r); }
    public function txInstalled(Request $r){ $r->merge(['type'=>'INSTALLED']); return $this->txStore($r); }

    /* ==================== MATERIAL REQUESTS ==================== */

    // GET /gudang/material-requests
    public function mrIndex(Request $r)
    {
        $q = MaterialRequest::query()
            ->with(['items.item'])
            ->when($r->status, fn($qq) => $qq->whereIn('status', (array)$r->status))
            ->when($r->reff_id_pelanggan, fn($qq) => $qq->where('reff_id_pelanggan', $r->reff_id_pelanggan))
            ->when($r->module_type && $r->module_id, function ($qq) use ($r) {
                $qq->where('module_type', $this->resolveModuleClass($r->module_type))
                   ->where('module_id', $r->module_id);
            })
            ->orderByDesc('id');

        return response()->json($q->paginate((int)$r->get('per_page', 20)));
    }

    // GET /gudang/material-requests/{mr}
    public function mrShow(MaterialRequest $mr)
    {
        $mr->load(['items.item','calonPelanggan']);
        return response()->json($mr);
    }

    // POST /gudang/material-requests
    public function mrStore(Request $r)
    {
        $v = Validator::make($r->all(), [
            'module_type' => ['required','string','in:sk,sr,App\\Models\\SkData,App\\Models\\SrData'],
            'module_id'   => ['required','integer'],
            'notes'       => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $data = $v->validated();
        $class = $this->resolveModuleClass($data['module_type']);
        $module = $class::findOrFail($data['module_id']);

        $mr = MaterialRequest::create([
            'module_type'       => $class,
            'module_id'         => $module->id,
            'reff_id_pelanggan' => $module->reff_id_pelanggan,
            'status'            => MaterialRequest::S_DRAFT,
            'notes'             => $data['notes'] ?? null,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        return response()->json($mr->load('items.item'), 201);
    }

    // POST /gudang/material-requests/{mr}/items  (add or update items)
    public function mrAddItem(Request $r, MaterialRequest $mr)
    {
        if ($mr->status !== MaterialRequest::S_DRAFT && $mr->status !== MaterialRequest::S_SUBMITTED) {
            return response()->json(['message' => 'Tidak bisa ubah item pada status ini.'], 422);
        }
        $v = Validator::make($r->all(), [
            'gudang_item_id' => ['required_without:gudang_item_code','integer','exists:gudang_items,id'],
            'gudang_item_code' => ['required_without:gudang_item_id','string','max:50'],
            'qty_requested' => ['required','numeric','min:0.001'],
            'unit' => ['nullable','string','max:32'],
            'notes' => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $payload = $v->validated();
        $itemId = $payload['gudang_item_id'] ?? null;
        if (!$itemId) {
            $gi = GudangItem::where('code', $payload['gudang_item_code'])->first();
            if (!$gi) {
                return response()->json(['message' => 'Item belum terdaftar di gudang. Lakukan pengadaan/master item dulu.'], 422);
            }
            $itemId = $gi->id;
        }

        $mri = MaterialRequestItem::updateOrCreate(
            ['material_request_id' => $mr->id, 'gudang_item_id' => $itemId],
            [
                'qty_requested' => $payload['qty_requested'],
                'unit'          => $payload['unit'] ?? optional($gi ?? GudangItem::find($itemId))->unit,
                'notes'         => $payload['notes'] ?? null,
            ]
        );

        return response()->json($mri->load('item'));
    }

    // POST /gudang/material-requests/{mr}/submit
    public function mrSubmit(MaterialRequest $mr)
    {
        if (!$mr->canSubmit()) return response()->json(['message'=>'Belum bisa submit (tidak ada item atau status tidak draft).'], 422);
        $mr->markSubmitted();
        return response()->json($mr->fresh('items.item'));
    }

    // POST /gudang/material-requests/{mr}/approve
    public function mrApprove(Request $r, MaterialRequest $mr)
    {
        if (!$mr->canApprove()) return response()->json(['message'=>'Status tidak valid untuk approve.'], 422);

        $v = Validator::make($r->all(), [
            'items' => ['nullable','array'],
            'items.*.id' => ['required_with:items','integer','exists:material_request_items,id'],
            'items.*.qty_approved' => ['required_with:items','numeric','min:0'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        DB::transaction(function () use ($mr, $v) {
            $data = $v->validated();
            if (!empty($data['items'])) {
                foreach ($data['items'] as $row) {
                    $it = MaterialRequestItem::where('material_request_id',$mr->id)->findOrFail($row['id']);
                    $it->qty_approved = $row['qty_approved'];
                    $it->save();
                }
            }
            if (!$mr->request_no) $mr->request_no = MaterialRequest::makeNumber('MR');
            $mr->markApproved();
        });

        return response()->json($mr->fresh('items.item'));
    }

    // POST /gudang/material-requests/{mr}/issue
    public function mrIssue(Request $r, MaterialRequest $mr)
    {
        if (!$mr->canIssue()) return response()->json(['message'=>'Status tidak valid untuk issue.'], 422);

        $v = Validator::make($r->all(), [
            'items' => ['nullable','array'],
            'items.*.id' => ['required_with:items','integer','exists:material_request_items,id'],
            'items.*.qty' => ['required_with:items','numeric','min:0.001'],
            'ref_no' => ['nullable','string','max:100'],
            'notes'  => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $payload = $v->validated();

        DB::transaction(function () use ($mr, $payload) {
            $items = $payload['items'] ?? $mr->items()->get()->map(fn($it) => ['id'=>$it->id, 'qty'=>max(0, (float)$it->qty_approved - (float)$it->qty_issued)])->toArray();

            foreach ($items as $row) {
                $it = MaterialRequestItem::where('material_request_id',$mr->id)->findOrFail($row['id']);
                $qty = (float)$row['qty'];
                if ($qty <= 0) continue;

                $onHand = $this->onHand($it->gudang_item_id);
                if ($qty > $onHand + 1e-9) {
                    throw new \RuntimeException("Stok tidak cukup untuk item {$it->item->code} (on hand: {$onHand})");
                }

                // Buat transaksi OUT
                GudangTransaction::create([
                    'gudang_item_id' => $it->gudang_item_id,
                    'type'           => 'OUT',
                    'qty'            => $qty,
                    'unit'           => $it->unit,
                    'ref_no'         => $payload['ref_no'] ?? $mr->request_no,
                    'notes'          => $payload['notes'] ?? ('Issue MR #'.$mr->id),
                    'sourceable_type'=> MaterialRequest::class,
                    'sourceable_id'  => $mr->id,
                    'created_by'     => Auth::id(),
                    'transacted_at'  => now(),
                ]);

                $it->applyIssue($qty);
            }

            $mr->markIssued();
        });

        return response()->json($mr->fresh('items.item'));
    }

    // POST /gudang/material-requests/{mr}/return
    public function mrReturn(Request $r, MaterialRequest $mr)
    {
        $v = Validator::make($r->all(), [
            'items' => ['required','array','min:1'],
            'items.*.id'  => ['required','integer','exists:material_request_items,id'],
            'items.*.qty' => ['required','numeric','min:0.001'],
            'ref_no' => ['nullable','string','max:100'],
            'notes'  => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $payload = $v->validated();

        DB::transaction(function () use ($mr, $payload) {
            foreach ($payload['items'] as $row) {
                $it = MaterialRequestItem::where('material_request_id',$mr->id)->findOrFail($row['id']);
                $qty = (float)$row['qty'];
                if ($qty <= 0) continue;

                GudangTransaction::create([
                    'gudang_item_id' => $it->gudang_item_id,
                    'type'           => 'RETURN',
                    'qty'            => $qty,
                    'unit'           => $it->unit,
                    'ref_no'         => $payload['ref_no'] ?? $mr->request_no,
                    'notes'          => $payload['notes'] ?? ('Return MR #'.$mr->id),
                    'sourceable_type'=> MaterialRequest::class,
                    'sourceable_id'  => $mr->id,
                    'created_by'     => Auth::id(),
                    'transacted_at'  => now(),
                ]);

                $it->applyReturn($qty);
            }
        });

        return response()->json($mr->fresh('items.item'));
    }

    // POST /gudang/material-requests/{mr}/reject
    public function mrReject(Request $r, MaterialRequest $mr)
    {
        $v = Validator::make($r->all(), [
            'items' => ['required','array','min:1'],
            'items.*.id'  => ['required','integer','exists:material_request_items,id'],
            'items.*.qty' => ['required','numeric','min:0.001'],
            'ref_no' => ['nullable','string','max:100'],
            'notes'  => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $payload = $v->validated();
        DB::transaction(function () use ($mr, $payload) {
            foreach ($payload['items'] as $row) {
                $it = MaterialRequestItem::where('material_request_id',$mr->id)->findOrFail($row['id']);
                $qty = (float)$row['qty'];
                if ($qty <= 0) continue;

                // Reject = keluar stok (barang rusak) → tipe REJECT
                $onHand = $this->onHand($it->gudang_item_id);
                if ($qty > $onHand + 1e-9) {
                    throw new \RuntimeException("Stok tidak cukup untuk reject item {$it->item->code} (on hand: {$onHand})");
                }

                GudangTransaction::create([
                    'gudang_item_id' => $it->gudang_item_id,
                    'type'           => 'REJECT',
                    'qty'            => $qty,
                    'unit'           => $it->unit,
                    'ref_no'         => $payload['ref_no'] ?? $mr->request_no,
                    'notes'          => $payload['notes'] ?? ('Reject MR #'.$mr->id),
                    'sourceable_type'=> MaterialRequest::class,
                    'sourceable_id'  => $mr->id,
                    'created_by'     => Auth::id(),
                    'transacted_at'  => now(),
                ]);

                $it->applyReject($qty);
            }
        });

        return response()->json($mr->fresh('items.item'));
    }

    /* ==================== HELPERS ==================== */

    private function onHand(int $gudangItemId): float
    {
        $row = DB::table('gudang_stock_balances')->where('gudang_item_id', $gudangItemId)->first();
        return (float) ($row->on_hand ?? 0.0);
    }

    private function resolveModuleClass(string $name): string
    {
        $n = strtolower($name);
        return match ($n) {
            'sk','app\\models\\skdata' => SkData::class,
            'sr','app\\models\\srdata' => SrData::class,
            default => throw new \InvalidArgumentException('module_type tidak valid'),
        };
    }
}
