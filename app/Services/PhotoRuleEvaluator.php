<?php

namespace App\Services;
use Illuminate\Support\Arr;

class PhotoRuleEvaluator
{
    /**
     * $rules   : definisi slot dari config (termasuk 'checks')
     * $aiRaw   : output dari OpenAIService::analyzeImageChecks()
     * Return   : ['status','score','checks','failed','warnings','notes']
     *  - status: 'passed' | 'failed'
     *  - score : 0–100 (rata-rata confidence x 100)
     *  - checks: daftar per-cek (id, passed, confidence, reason)
     *  - failed: id cek yang gagal
     *  - warnings: id cek borderline (lulus dengan peringatan)
     *  - notes : array catatan ringkas
     */
    public function evaluate(array $rules, array $aiRaw): array
    {
        $spec = $this->normalizeChecks(Arr::get($rules, 'checks', []));
        $raw  = collect(Arr::get($aiRaw, 'checks', []))->keyBy('id');

        $results  = [];
        $failed   = [];
        $warnings = [];
        $notes    = [];

        foreach ($spec as $id => $cfg) {
            // ✅ normalisasi confidence ke 0–1
            $confRaw = (float) ($raw[$id]['confidence'] ?? (isset($raw[$id]['passed']) ? (int)$raw[$id]['passed'] : 0));
            $conf    = $confRaw > 1 ? $confRaw / 100 : $confRaw;

            $passMin = (float) ($cfg['min_confidence'] ?? 0.65);
            $warnMin = (float) ($cfg['warn_min']       ?? max(0.50, $passMin - 0.15));

            if ($conf >= $passMin) {
                $results[] = ['id'=>$id, 'passed'=>true,  'confidence'=>$conf, 'reason'=>'ok'];
            } elseif ($conf >= $warnMin) {
                $results[] = ['id'=>$id, 'passed'=>true,  'confidence'=>$conf, 'reason'=>'borderline'];
                $warnings[] = $id;
                $notes[] = ($cfg['label'] ?? $id).' borderline (skor ~'.round($conf*100).'%)';
            } else {
                $results[] = ['id'=>$id, 'passed'=>false, 'confidence'=>$conf, 'reason'=>'low_confidence'];
                $failed[]  = $id;
                $notes[] = ($cfg['label'] ?? $id).' tidak memenuhi (skor ~'.round($conf*100).'%)';
            }
        }

        // ✅ skor akhir 0–100 (bukan 0–10000)
        if (count($results)) {
            $score = round(collect($results)->avg('confidence') * 100);
        } else {
            $rawScore = (float) ($aiRaw['score'] ?? 0);
            $rawScore = $rawScore > 1 ? $rawScore / 100 : $rawScore; // normalisasi
            $score = round($rawScore * 100);
        }

        $status = empty($failed) ? 'passed' : 'failed';

        $detected = Arr::get($aiRaw, 'checks.detected', []);
        $checksOut = $results;
        if (!empty($detected)) {
            $checksOut = [
                'detected' => $detected,
                'items'    => $results,
            ];
        }

        return [
            'status'   => $status,
            'score'    => (int) $score,   // 0–100
            'checks'   => $checksOut,
            'failed'   => $failed,
            'warnings' => $warnings,
            'notes'    => $notes,
        ];
    }

    private function normalizeChecks(array $checks): array
    {
        $out = [];
        foreach ($checks as $k => $v) {
            if (is_string($k)) { $out[$k] = (array) $v; continue; }
            if (is_array($v) && !empty($v['id'])) { $out[(string)$v['id']] = $v; }
        }
        return $out;
    }

}
