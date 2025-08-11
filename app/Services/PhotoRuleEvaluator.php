<?php

namespace App\Services;

class PhotoRuleEvaluator
{
    /**
     * @param array $rules  Aturan dari config (required_objects, min_resolution, dst)
     * @param array $ai     Hasil AI ter-normalisasi:
     *                      ['objects'=>[['name','confidence'],...],
     *                       'image'=>['width','height'],
     *                       'score'=>float|null,'notes'=>?string]
     * @return array{status:string, score:?float, checks:array, notes:?string}
     */
    public function evaluate(array $rules, array $ai): array
    {
        $checks = [];
        $passed = true;

        // objek wajib
        $required = array_map('strtolower', $rules['required_objects'] ?? []);
        if ($required) {
            $detected = collect($ai['objects'] ?? [])
                ->pluck('name')->filter()->map(fn($s)=>strtolower($s))->unique()->all();

            foreach ($required as $obj) {
                $ok = in_array($obj, $detected, true);
                $checks[] = ['type'=>'required_object','name'=>$obj,'passed'=>$ok];
                if (!$ok) $passed = false;
            }
        }

        // resolusi minimal
        if (!empty($rules['min_resolution']) && !empty($ai['image'])) {
            [$minW,$minH] = $rules['min_resolution'];
            $w = $ai['image']['width']  ?? 0;
            $h = $ai['image']['height'] ?? 0;
            $ok = $w >= $minW && $h >= $minH;
            $checks[] = ['type'=>'min_resolution','min'=>[$minW,$minH],'actual'=>[$w,$h],'passed'=>$ok];
            if (!$ok) $passed = false;
        }

        return [
            'status' => $passed ? 'passed' : 'failed',
            'score'  => $ai['score'] ?? null,
            'checks' => $checks,
            'notes'  => $passed ? null : ($ai['notes'] ?? 'Syarat belum terpenuhi'),
        ];
    }
}
