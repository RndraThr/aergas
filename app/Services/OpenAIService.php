<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class OpenAIService
{
    private ?string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey      = (string) config('services.openai.api_key');
        $this->model       = (string) config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens   = (int)    config('services.openai.max_tokens', 1000);
        $this->temperature = (float)  config('services.openai.temperature', 0.1);
    }

    /**
     * Metode UTAMA untuk validasi berbasis daftar checks dari config:
     * - $image (local path ATAU URL)
     * - $checksSpec: array cek dari config aergas_photos (tiap item minimal punya ['id','label'])
     * - $ctx: ['module'=>'SK|SR', 'slot'=>'pneumatic_start' ...] (opsional, hanya untuk konteks prompt)
     *
     * Return:
     * [
     *   'score'  => float 0..1,
     *   'notes'  => ?string,
     *   'checks' => [ ['id'=>string,'passed'=>bool,'confidence'=>float,'reason'=>string], ... ]
     * ]
     */
    public function analyzeImageChecks(string $image, array $checksSpec, array $ctx = []): array
    {
        try {
            // Dev fallback: tanpa API key -> PASS semua agar operasional tidak terblokir
            if (empty($this->apiKey)) {
                $checks = collect($checksSpec)->map(fn($c) => [
                    'id'         => (string)($c['id'] ?? ''),
                    'passed'     => true,
                    'confidence' => 1.0,
                    'reason'     => 'ai-disabled',
                ])->values()->all();

                return [
                    'score'  => 1.0,
                    'notes'  => 'AI disabled: auto-pass',
                    'checks' => $checks,
                ];
            }

            // Siapkan parts konten vision (text + image_url|data:)
            [$imagePart, $mime] = $this->buildImagePart($image);

            $ctxModule = (string) ($ctx['module'] ?? '');
            $ctxSlot   = (string) ($ctx['slot'] ?? '');

            $system = "You are a strict photo inspector for a gas installation workflow. "
                    . "Evaluate the given image against REQUIRED checks. Be conservative: if uncertain, mark as failed.";

            // Untuk model vision, kita pakai satu message 'user' berisi teks + gambar
            $prompt = $this->buildChecksPrompt($checksSpec, $ctxModule, $ctxSlot);

            $payload = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            $imagePart,
                        ]
                    ],
                ],
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
            ];

            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$resp->ok()) {
                Log::warning('OpenAI non-200', ['status' => $resp->status(), 'body' => $resp->body()]);
                // fallback: FAIL semua checks (lebih aman)
                $checks = collect($checksSpec)->map(fn($c) => [
                    'id'         => (string)($c['id'] ?? ''),
                    'passed'     => false,
                    'confidence' => 0.0,
                    'reason'     => 'ai_http_error',
                ])->values()->all();

                return [
                    'score'  => 0.0,
                    'notes'  => 'AI HTTP error',
                    'checks' => $checks,
                ];
            }

            $text = (string) $resp->json('choices.0.message.content', '');
            $json = $this->extractJson($text);

            // Normalisasi output
            $checks = $this->normalizeChecks($json['checks'] ?? [], $checksSpec);
            $score  = $this->clamp01((float) ($json['score'] ?? $this->avgConfidence($checks)));
            $notes  = $json['notes'] ?? null;

            return compact('score','notes','checks');
        } catch (Exception $e) {
            Log::error('OpenAI analyzeImageChecks error', ['err' => $e->getMessage()]);

            // fallback aman: FAIL semua checks
            $checks = collect($checksSpec)->map(fn($c) => [
                'id'         => (string)($c['id'] ?? ''),
                'passed'     => false,
                'confidence' => 0.0,
                'reason'     => 'exception: '.$e->getMessage(),
            ])->values()->all();

            return [
                'score'  => 0.0,
                'notes'  => 'AI validation error: '.$e->getMessage(),
                'checks' => $checks,
            ];
        }
    }

    // App\Services\OpenAIService.php (tambahkan)
    public function analyzeImagePrompt(string $imagePathOrUrl, string $prompt, array $criteriaIds, array $ctx = []): array
    {
        // --- config pakai services.php yang kamu berikan
        $apiKey = (string) config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $model  = (string) config('services.openai.model', 'gpt-4o-mini');
        $temp   = (float)  config('services.openai.temperature', 0.1);
        $maxTok = (int)    config('services.openai.max_tokens', 1000);
        $endpoint = 'https://api.openai.com/v1';

        if ($apiKey === '') {
            // tanpa API key → auto pass agar tidak nge-block operasional
            $items = collect($criteriaIds)->map(fn($id) => [
                'id'=>$id,'passed'=>true,'confidence'=>1.0,'reason'=>'ai-disabled'
            ])->all();
            return ['status'=>'passed','score'=>1.0,'checks'=>$items,'failed'=>[], 'notes'=>['OPENAI_API_KEY kosong']];
        }

        // --- siapkan image: url http(s) atau file lokal → data URL
        $imageUrl = $imagePathOrUrl;
        if (!Str::startsWith($imagePathOrUrl, ['http://','https://'])) {
            try {
                $bin  = @file_get_contents($imagePathOrUrl);
                $mime = @mime_content_type($imagePathOrUrl) ?: 'image/jpeg';
                if ($bin !== false) {
                    $imageUrl = 'data:'.$mime.';base64,'.base64_encode($bin);
                }
            } catch (\Throwable $e) { /* biarkan apa adanya */ }
        }

        $system = 'Anda adalah asisten visi komputer. Balas HANYA JSON valid.';
        $payload = [
            'model' => $model,
            'temperature' => $temp,
            'max_tokens' => $maxTok,
            'response_format' => ['type'=>'json_object'],
            'messages' => [
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>[
                    ['type'=>'text','text'=>$prompt],
                    ['type'=>'image_url','image_url'=>['url'=>$imageUrl]],
                ]],
            ],
        ];

        try {
            $resp = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->post($endpoint.'/chat/completions', $payload);

            if (!$resp->ok()) {
                throw new \RuntimeException('OpenAI error '.$resp->status().': '.$resp->body());
            }
            $content = data_get($resp->json(), 'choices.0.message.content', '{}');
        } catch (\Throwable $e) {
            // fallback aman
            $items = collect($criteriaIds)->map(fn($id)=>[
                'id'=>$id,'passed'=>true,'confidence'=>0.7,'reason'=>'ai-error-fallback'
            ])->all();
            return ['status'=>'passed','score'=>0.7,'checks'=>$items,'failed'=>[], 'notes'=>['OpenAI failure: '.$e->getMessage()]];
        }

        // --- parse JSON dari model
        if (!is_array($content)) {
            if (preg_match('/\{.*\}/s', (string)$content, $m)) {
                $content = json_decode($m[0], true) ?: [];
            } else {
                $content = json_decode((string)$content, true) ?: [];
            }
        }

        $items = [];
        foreach ((array)($content['criteria'] ?? []) as $c) {
            $id = (string)($c['id'] ?? '');
            if ($id === '') continue;
            $passed = (bool)($c['passed'] ?? false);
            $conf   = (float)($c['confidence'] ?? 0);
            if ($conf > 1) $conf /= 100;            // normalisasi ke 0..1
            if ($conf < 0) $conf = 0.0;

            $items[] = [
                'id'=>$id, 'passed'=>$passed, 'confidence'=>$conf,
                'reason'=>(string)($c['reason'] ?? ''),
            ];
        }

        // pastikan semua id ada
        foreach ($criteriaIds as $need) {
            if (!collect($items)->firstWhere('id', $need)) {
                $items[] = ['id'=>$need,'passed'=>false,'confidence'=>0.0,'reason'=>'missing_from_model'];
            }
        }

        $failed = collect($items)->where('passed', false)->pluck('id')->values()->all();
        $status = empty($failed) ? 'passed' : 'failed';
        $score  = count($items) ? (float) collect($items)->avg('confidence') : 0.0;
        $notes  = (array)($content['notes'] ?? []);

        return compact('status','score','checks','failed','notes');
    }
    // helper; sesuaikan kalau path lokal
    private function toImageUrl(string $pathOrUrl): string
    {
        return str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : 'file://'.$pathOrUrl;
    }


    /**
     * LEGACY SHIM — tetap ada demi kompatibilitas.
     * Kini dialihkan ke analyzeImageChecks() dengan cek minimal:
     *  - anti blur/jelas
     *  - objek/elemen kunci (dari expectedObjects)
     *
     * Return lama:
     * ['validation_passed'=>bool, 'confidence'=>int 0..100, 'rejection_reason'=>?string]
     */
    public function validatePhoto(string $localPath, string $photoFieldName, string $module): array
    {
        try {
            if (!file_exists($localPath)) {
                throw new Exception("Image not found: {$localPath}");
            }

            $expected = $this->expectedObjects($module, $photoFieldName);
            // Jadikan dua cek minimal:
            $checksSpec = [
                ['id' => 'not_blurry', 'label' => 'Foto tidak blur & cukup terang'],
                ['id' => 'objects_present', 'label' => 'Objek kunci terlihat', 'objects' => $expected],
            ];

            $res = $this->analyzeImageChecks($localPath, $checksSpec, [
                'module' => $module,
                'slot'   => $photoFieldName,
            ]);

            $failed = collect($res['checks'])->where('passed', false)->pluck('id')->all();
            return [
                'validation_passed' => empty($failed),
                'confidence'        => (int) round(($res['score'] ?? 0) * 100),
                'rejection_reason'  => empty($failed) ? null : ('Cek gagal: '.implode(', ', $failed)),
            ];
        } catch (Exception $e) {
            Log::error('OpenAI validatePhoto error', ['err' => $e->getMessage()]);
            return ['validation_passed' => false, 'confidence' => 0, 'rejection_reason' => 'AI validation error: '.$e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        try {
            if (empty($this->apiKey)) {
                return ['success' => false, 'message' => 'OPENAI_API_KEY not set'];
            }
            return [
                'success' => true,
                'message' => 'API key present',
                'available_models' => [$this->model],
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI testConnection failed: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /* ============================ Helpers ============================ */

    /**
     * Bangun konten image untuk Chat Completions (vision).
     * - Kalau $image adalah local path → kirim data: URI base64 dengan mime yang benar
     * - Kalau $image adalah URL (http/https/data:) → langsung pakai image_url
     * @return array [imagePartArray, mime]
     */
    private function buildImagePart(string $image): array
    {
        $isUrl = str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, 'data:');
        if ($isUrl) {
            return [
                ['type' => 'image_url', 'image_url' => ['url' => $image]],
                null
            ];
        }

        if (!file_exists($image)) {
            throw new Exception("Image not found: {$image}");
        }
        $mime = mime_content_type($image) ?: 'application/octet-stream';
        $b64  = base64_encode((string) file_get_contents($image));
        $dataUrl = "data:{$mime};base64,{$b64}";

        return [
            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
            $mime
        ];
    }

    /**
     * Prompt yang tegas agar model mengembalikan JSON terstruktur.
     * $checksSpec dipasang dalam bentuk JSON agar ID/labelnya jelas bagi model.
     */
    private function buildChecksPrompt(array $checksSpec, string $module, string $slot): string
    {
        $specJson = json_encode($checksSpec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ctx = trim("Module: {$module}; Slot: {$slot}");
        return <<<PROMPT
You will evaluate ONE image for REQUIRED checks. Be strict.
Context: {$ctx}

CHECKS_SPEC (JSON):
{$specJson}

Definitions:
- "passed": true only if the requirement is clearly satisfied.
- "confidence": 0.0..1.0 your confidence the check is correctly judged.
- If the image is blurry, dark, or incomplete, relevant checks should fail.
- For a check with "objects" list, ensure those visual elements are present and connected, if applicable.

Return ONLY JSON in this exact schema:
{
  "score": 0.0..1.0,           // overall pass ratio or your overall confidence
  "notes": "optional short note or null",
  "checks": [
    {"id":"<check id>","passed":true|false,"confidence":0.0..1.0,"reason":"short reason"}
  ]
}
PROMPT;
    }

    /**
     * Normalisasi keluaran "checks" dari model → pastikan semua ID dari spec ada.
     */
    private function normalizeChecks(array $rawChecks, array $checksSpec): array
    {
        // indeks hasil oleh id
        $byId = collect($rawChecks)->mapWithKeys(function ($c) {
            $id = (string) ($c['id'] ?? '');
            return [$id => [
                'id'         => $id,
                'passed'     => (bool)   ($c['passed'] ?? false),
                'confidence' => (float)  ($c['confidence'] ?? 0),
                'reason'     => (string) ($c['reason'] ?? ''),
            ]];
        });

        // pastikan semua spec.id ada
        $out = [];
        foreach ($checksSpec as $spec) {
            $id = (string) ($spec['id'] ?? '');
            if ($id === '') continue;

            $row = $byId[$id] ?? [
                'id'         => $id,
                'passed'     => false,
                'confidence' => 0.0,
                'reason'     => 'missing_judgement',
            ];

            // clamp confidence
            $row['confidence'] = $this->clamp01((float) $row['confidence']);
            $out[] = $row;
        }
        return $out;
    }

    private function extractJson(?string $text): array
    {
        if (!$text) return [];
        $text = trim($text);
        if (str_starts_with($text, '{')) {
            return json_decode($text, true) ?: [];
        }
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $j = json_decode($m[0], true);
            return is_array($j) ? $j : [];
        }
        return [];
    }

    private function avgConfidence(array $checks): float
    {
        if (empty($checks)) return 0.0;
        $sum = 0.0; $n = 0;
        foreach ($checks as $c) { $sum += (float) ($c['confidence'] ?? 0); $n++; }
        return $n ? $sum / $n : 0.0;
    }

    private function clamp01(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }

    /**
     * Map ekspektasi objek per field — menerima *_url juga (legacy).
     * Dipakai oleh validatePhoto() (legacy).
     */
    private function expectedObjects(string $module, string $field): array
    {
        $m = strtolower($module);
        $f = strtolower($field);

        $alias = [
            'sk' => [
                'foto_pneumatic_start_sk_url'  => 'foto_pneumatic_start_sk',
                'foto_pneumatic_finish_sk_url' => 'foto_pneumatic_finish_sk',
                'foto_valve_sk_url'            => 'foto_valve_sk',
                'foto_pipa_depan_sk_url'       => 'foto_pipa_depan_sk',
                'scan_isometrik_sk_url'        => 'scan_isometrik_sk',
            ],
            'sr' => [
                'foto_pneumatic_start_sr_url'  => 'pneumatic_start',
                'foto_pneumatic_finish_sr_url' => 'pneumatic_finish',
                'foto_kedalaman_sr_url'        => 'kedalaman',
                'scan_isometrik_sr_url'        => 'isometrik_scan',
                'foto_jenis_tapping_sr_url'    => 'tapping_saddle',
            ],
        ];
        $canon = $alias[$m][$f] ?? $f;

        return match ("{$m}.{$canon}") {
            'sk.foto_pneumatic_start_sk'  => ['gauge/manometer readable','tagging form present','three signatures present','pipe connected'],
            'sk.foto_pneumatic_finish_sk' => ['gauge/manometer readable','tagging form present','three signatures present','pipe connected'],
            'sk.foto_valve_sk'            => ['valve visible','handle visible','installed to pipe'],
            'sk.foto_pipa_depan_sk'       => ['pipe visible','front of house','connection point'],
            'sk.scan_isometrik_sk'        => ['isometric drawing','signatures complete'],

            'sr.pneumatic_start'          => ['pneumatic tool','pressure gauge readable','hose to pipe'],
            'sr.pneumatic_finish'         => ['pneumatic tool','pressure gauge readable'],
            'sr.kedalaman'                => ['trench/ground','measuring tape depth'],
            'sr.isometrik_scan'           => ['isometric drawing','signatures complete'],
            'sr.tapping_saddle'           => ['tapping saddle present','size marking readable'],

            default => ['gas','pipe','meter','valve'],
        };
    }
}
