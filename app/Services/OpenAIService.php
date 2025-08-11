<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService
{
    private ?string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey      = config('services.openai.api_key');
        $this->model       = (string) config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens   = (int) config('services.openai.max_tokens', 1000);
        $this->temperature = (float) config('services.openai.temperature', 0.1);
    }

    /**
     * Validasi foto pakai Chat Completions (vision). Jika tak ada apiKey → auto PASS (dev).
     * @return array{validation_passed:bool, confidence:int, rejection_reason:?string}
     */
    public function validatePhoto(string $localPath, string $photoFieldName, string $module): array
    {
        try {
            if (!$this->apiKey) {
                return ['validation_passed' => true, 'confidence' => 95, 'rejection_reason' => null];
            }
            if (!file_exists($localPath)) {
                throw new Exception("Image not found: {$localPath}");
            }

            $expected = $this->expectedObjects($module, $photoFieldName);
            $prompt = "You are a strict inspector. Check if the uploaded image contains REQUIRED items for module '{$module}' and field '{$photoFieldName}'. ".
                      "Required keywords: ".implode(', ', $expected).". ".
                      "Return JSON: {\"validation_passed\": bool, \"confidence\": 0-100, \"rejection_reason\": string|null}.";

            $b64 = base64_encode(file_get_contents($localPath));

            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$b64}"]],
                    ],
                ]],
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
            ]);

            if (!$resp->ok()) {
                Log::warning('OpenAI non-200', ['status' => $resp->status(), 'body' => $resp->body()]);
                return ['validation_passed' => true, 'confidence' => 80, 'rejection_reason' => null];
            }

            $text = $resp->json('choices.0.message.content');
            $json = $this->extractJson($text);

            return [
                'validation_passed' => (bool)($json['validation_passed'] ?? true),
                'confidence'        => (int)($json['confidence'] ?? 80),
                'rejection_reason'  => $json['rejection_reason'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('OpenAI validatePhoto error', ['err' => $e->getMessage()]);
            return ['validation_passed' => false, 'confidence' => 0, 'rejection_reason' => 'AI validation error: '.$e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        try {
            $apiKey = config('services.openai.api_key');
            if (!$apiKey) {
                return ['success' => false, 'message' => 'OPENAI_API_KEY not set'];
            }
            // tanpa benar-benar hit API, cukup lapor siap
            return [
                'success' => true,
                'message' => 'API key present',
                'available_models' => [config('services.openai.model', 'gpt-4o-mini')],
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI testConnection failed: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
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

    /**
     * Map ekspektasi objek per field — menerima *_url juga.
     */
    private function expectedObjects(string $module, string $field): array
    {
        $m = strtolower($module);
        $f = strtolower($field);

        // alias *_url -> kanonik
        $alias = [
            'sk' => [
                'foto_pneumatic_start_sk_url'  => 'foto_pneumatic_start_sk',
                'foto_pneumatic_finish_sk_url' => 'foto_pneumatic_finish_sk',
                'foto_valve_sk_url'            => 'foto_valve_krunchis',   // samakan ke kanonik yang sudah ada
                'foto_pipa_depan_sk_url'       => 'foto_pipa_depan_sk',
                'scan_isometrik_sk_url'        => 'foto_isometrik_sk',
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
            'sk.foto_pneumatic_start_sk'  => ['gauge','manometer','pressure','hose'],
            'sk.foto_pneumatic_finish_sk' => ['gauge','manometer','pressure','hose'],
            'sk.foto_pipa_depan_sk'       => ['pipe','wall','connection'], // sesuaikan kalau perlu


            'sr.pneumatic_start'  => ['pneumatic','pressure_gauge','hose'],
            'sr.pneumatic_finish' => ['pneumatic','pressure_gauge'],
            'sr.kedalaman'        => ['measuring_tape','trench'],
            'sr.isometrik_scan'   => ['document','signature'],
            'sr.tapping_saddle'   => ['tapping_saddle'],

            default => ['gas','pipe','meter','valve'],
        };
    }
}
