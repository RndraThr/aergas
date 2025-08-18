<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class CalonPelangganImport implements ToCollection, WithHeadingRow
{
    private bool $dryRun;
    private int $headingRow;
    private int $chunk = 500;

    private string $table = 'calon_pelanggan';
    private array $cols  = [];
    private array $buf   = [];

    // fill-down utk cell merge
    private ?string $lastKel = null;
    private ?string $lastPdk = null;

    protected array $results = ['success'=>0,'updated'=>0,'skipped'=>0,'failed'=>[]];

    public function __construct(bool $dryRun = false, int $headingRow = 1)
    {
        $this->dryRun     = $dryRun;
        $this->headingRow = $headingRow;

        // Pastikan header tidak di-slug (biar apa adanya)
        HeadingRowFormatter::default('none');

        if (Schema::hasTable($this->table)) {
            $this->cols = Schema::getColumnListing($this->table);
        }
    }

    public function headingRow(): int { return $this->headingRow; }

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            if (empty(array_filter($row->toArray(), fn($v)=>$v!==null && $v!==''))) {
                $this->results['skipped']++; continue;
            }

            [$ok, $data, $errors] = $this->mapAndValidate($row->toArray());
            $rowNo = $this->headingRow + 1 + $i;

            if (!$ok) {
                $this->results['failed'][] = ['row'=>$rowNo, 'errors'=>$errors, 'data'=>$data];
                continue;
            }

            if ($this->dryRun) {
                $exists = DB::table($this->table)
                    ->where('reff_id_pelanggan', $data['reff_id_pelanggan'])
                    ->exists();
                $exists ? $this->results['updated']++ : $this->results['success']++;
                continue;
            }

            $this->buf[] = $this->toDbRow($data);
            if (count($this->buf) >= $this->chunk) $this->flush();
        }

        $this->flush();
    }

    public function getResults(): array { return $this->results; }

    // ---------------- helpers ----------------

    /** Normalisasi header:
     *  - hapus NBSP, CR/LF/TAB, ZWSP/ZWNJ/ZWJ, BOM
     *  - ganti '_', '.', '/', '\' jadi spasi
     *  - lowercase & rapikan spasi
     */
    private function normKey(string $k): string
    {
        $k = trim($k);
        $k = str_replace("\xC2\xA0", ' ', $k); // NBSP → space
        $k = preg_replace('/[\x00-\x1F\x7F\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $k); // control/zero-width
        $k = str_replace(['_', '.', '/', '\\'], ' ', $k);
        $k = preg_replace('/\s+/u', ' ', $k);
        return mb_strtolower($k, 'UTF-8');
    }

    private function pick(array $normRow, array $cands): ?string
    {
        foreach ($cands as $c) {
            $key = $this->normKey($c);
            if (array_key_exists($key, $normRow)) {
                $v = $normRow[$key];
                return is_string($v) ? trim($v) : ($v === null ? null : (string)$v);
            }
        }
        return null;
    }

    private function mapAndValidate(array $r): array
    {
        // bentuk row dengan key yang sudah dinormalisasi
        $n = [];
        foreach ($r as $k=>$v) $n[$this->normKey((string)$k)] = is_string($v) ? trim($v) : $v;

        // === mapping sesuai permintaan ===
        $reff   = $this->pick($n, ['id_reff','id reff','id referensi']);
        $nama   = $this->pick($n, ['nama']);
        $hp     = $this->pick($n, ['nomor_ponsel','nomor ponsel','no_telepon','no telepon','telepon','no hp','nomor hp']);
        $alamat = $this->pick($n, ['alamat']);
        $rt     = $this->pick($n, ['rt']);
        $rw     = $this->pick($n, ['rw']);
        $kel    = $this->pick($n, ['kelurahan','id kelurahan','kelurahan/desa','desa/kelurahan','desa']);
        $pdk    = $this->pick($n, ['padukuhan','dusun']);

        // fallback REFF dari ujung alamat (mis. "... AF6518")
        if (!$reff && $alamat) {
            if (preg_match('/\b([A-Z]{2,5}[0-9]{3,8})\b(?!.*\b[A-Z]{2,5}[0-9]{3,8}\b)/u', $alamat, $m)) {
                $reff   = $m[1];
                $alamat = trim(preg_replace('/\s*'.preg_quote($reff,'/').'\s*$/u','', $alamat));
            }
        }

        // fill-down (atasi cell merge)
        if ($kel === null || $kel === '') $kel = $this->lastKel ?? null;
        if ($pdk === null || $pdk === '') $pdk = $this->lastPdk ?? null;
        if ($kel) $this->lastKel = $kel;
        if ($pdk) $this->lastPdk = $pdk;

        $data = [
            'reff_id_pelanggan' => $reff,
            'nama_pelanggan'    => $nama,
            'no_telepon'        => $hp ?? '',
            'alamat'            => $alamat,
            'rt'                => $rt,
            'rw'                => $rw,
            'kelurahan'         => $kel,
            'padukuhan'         => $pdk,
        ];

        // VALIDASI — sesuaikan skema tabel
        $v = Validator::make($data, [
            'reff_id_pelanggan' => ['required','string','max:50'],
            'nama_pelanggan'    => ['required','string','max:255'],
            'alamat'            => ['required','string'],
            'no_telepon'        => ['nullable','string','max:20'],
            'rt'                => ['nullable','string','max:10'],
            'rw'                => ['nullable','string','max:10'],
            'kelurahan'         => ['nullable','string','max:120'],
            'padukuhan'         => ['nullable','string','max:120'],
        ]);

        return [$v->passes(), $data, $v->errors()->all()];
    }

    private function toDbRow(array $d): array
    {
        $row = [
            'reff_id_pelanggan' => $d['reff_id_pelanggan'],
            'nama_pelanggan'    => $d['nama_pelanggan'],
            'alamat'            => $d['alamat'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
        // tulis hanya kolom yang ada di tabel
        if (in_array('no_telepon',$this->cols,true)) $row['no_telepon'] = $d['no_telepon'] ?? '';
        if (in_array('rt',$this->cols,true))         $row['rt']         = $d['rt'] ?? null;
        if (in_array('rw',$this->cols,true))         $row['rw']         = $d['rw'] ?? null;
        if (in_array('kelurahan',$this->cols,true))  $row['kelurahan']  = $d['kelurahan'] ?? null;
        if (in_array('padukuhan',$this->cols,true))  $row['padukuhan']  = $d['padukuhan'] ?? null;

        return $row;
    }

    private function flush(): void
    {
        if (!$this->buf) return;

        $keys = array_column($this->buf, 'reff_id_pelanggan');
        $exist = DB::table($this->table)
            ->whereIn('reff_id_pelanggan', $keys)
            ->pluck('reff_id_pelanggan')->all();
        $set = array_flip($exist);

        foreach ($this->buf as $r) {
            isset($set[$r['reff_id_pelanggan']]) ? $this->results['updated']++ : $this->results['success']++;
        }

        $updateCols = array_values(array_filter([
            'nama_pelanggan',
            'alamat',
            in_array('no_telepon',$this->cols,true) ? 'no_telepon' : null,
            in_array('rt',$this->cols,true)         ? 'rt'         : null,
            in_array('rw',$this->cols,true)         ? 'rw'         : null,
            in_array('kelurahan',$this->cols,true)  ? 'kelurahan'  : null,
            in_array('padukuhan',$this->cols,true)  ? 'padukuhan'  : null,
            'updated_at',
        ]));

        DB::table($this->table)->upsert($this->buf, ['reff_id_pelanggan'], $updateCols);
        $this->buf = [];
    }

    private function toString($v): ?string
    {
        if ($v === null) return null;
        if (is_numeric($v)) return rtrim(rtrim(number_format((float)$v, 10, '.', ''), '0'), '.');
        return trim((string)$v);
    }
}
