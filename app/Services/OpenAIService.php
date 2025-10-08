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
        $this->apiKey      = (string) config('services.openai.api_key');
        $this->model       = (string) config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens   = (int)    config('services.openai.max_tokens', 1000);
        $this->temperature = (float)  config('services.openai.temperature', 0.1);
    }

    /**
     * MAIN METHOD: Validate photo using custom prompt
     *
     * @param string $imagePath Local file path or URL
     * @param string $customPrompt Your specific validation instruction
     * @param array $context Additional context (module, slot, etc.)
     * @return array ['passed' => bool, 'reason' => string, 'confidence' => float, 'raw_response' => string]
     */
    public function validatePhotoWithPrompt(string $imagePath, string $customPrompt, array $context = []): array
    {
        try {
            // Dev mode: auto-pass if no API key
            if (empty($this->apiKey)) {
                return [
                    'passed' => true,
                    'reason' => 'AI disabled - auto pass for development',
                    'confidence' => 1.0,
                    'raw_response' => 'API key not configured'
                ];
            }

            // PDF auto-pass (can't analyze visually)
            if ($this->isPdfFile($imagePath)) {
                return [
                    'passed' => true,
                    'reason' => 'PDF file - manual review required',
                    'confidence' => 1.0,
                    'raw_response' => 'PDF file detected'
                ];
            }

            // Build image content for vision API
            $imageContent = $this->buildImageContent($imagePath);

            // Create system prompt for consistency
            $systemPrompt = $this->buildSystemPrompt();

            // Create user prompt with custom instruction
            $userPrompt = $this->buildUserPrompt($customPrompt, $context);

            // Call OpenAI Vision API
            $response = $this->callOpenAI($systemPrompt, $userPrompt, $imageContent);

            // Parse and validate response
            return $this->parseAIResponse($response);

        } catch (Exception $e) {
            Log::error('OpenAI validation error', [
                'error' => $e->getMessage(),
                'image' => $imagePath,
                'context' => $context
            ]);

            // Safe fallback: reject with clear error
            return [
                'passed' => false,
                'reason' => 'AI validation error: ' . $e->getMessage(),
                'confidence' => 0.0,
                'raw_response' => $e->getMessage()
            ];
        }
    }

    /**
     * Build image content for OpenAI Vision API
     */
    private function buildImageContent(string $imagePath): array
    {
        // If it's a URL, check if it's Google Drive and needs conversion
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            // Google Drive URLs need special handling - download and encode to base64
            if (str_contains($imagePath, 'drive.google.com')) {
                return $this->downloadAndEncodeImage($imagePath);
            }

            // Other URLs - try direct URL (might work for public images)
            return [
                'type' => 'image_url',
                'image_url' => ['url' => $imagePath]
            ];
        }

        // Local file - convert to base64
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: {$imagePath}");
        }

        $imageData = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $base64 = base64_encode($imageData);

        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => "data:{$mimeType};base64,{$base64}"
            ]
        ];
    }

    /**
     * Download image from Google Drive and encode to base64
     * Uses authenticated GoogleDriveService for private files
     */
    private function downloadAndEncodeImage(string $driveUrl): array
    {
        try {
            // Extract file ID from Google Drive URL
            $fileId = null;
            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $driveUrl, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $driveUrl, $matches)) {
                $fileId = $matches[1];
            }

            if (!$fileId) {
                throw new Exception("Could not extract file ID from Google Drive URL");
            }

            // Use GoogleDriveService for authenticated download
            $driveService = app(\App\Services\GoogleDriveService::class);
            $imageData = $driveService->downloadFileContent($fileId);

            // Detect mime type from content
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

            // Validate it's an image
            if (!str_starts_with($mimeType, 'image/')) {
                throw new Exception("Downloaded file is not an image (detected: {$mimeType})");
            }

            $base64 = base64_encode($imageData);

            Log::info('Google Drive image downloaded and encoded', [
                'file_id' => $fileId,
                'mime_type' => $mimeType,
                'size' => strlen($imageData)
            ]);

            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$base64}"
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to download Google Drive image', [
                'url' => $driveUrl,
                'error' => $e->getMessage()
            ]);

            throw new Exception("Google Drive image download failed: {$e->getMessage()}");
        }
    }

    /**
     * Build system prompt for consistent behavior
     */
    private function buildSystemPrompt(): string
    {
        return "You are a precise photo validator for gas installation documentation.

CRITICAL INSTRUCTIONS:
1. Follow the user's validation criteria EXACTLY
2. Be conservative - if unsure, REJECT the photo
3. Give specific, actionable reasons for rejection
4. Respond ONLY in the exact JSON format requested
5. Be consistent in your judgments

Your role is to ensure photo quality and completeness for safety documentation.";
    }

    /**
     * Build user prompt with custom validation instruction
     */
    private function buildUserPrompt(string $customPrompt, array $context = []): string
    {
        $contextInfo = '';
        if (!empty($context['module'])) {
            $contextInfo .= "Module: {$context['module']}\n";
        }
        if (!empty($context['slot'])) {
            $contextInfo .= "Photo Type: {$context['slot']}\n";
        }
        if (!empty($context['customer'])) {
            $contextInfo .= "Customer: {$context['customer']}\n";
        }

        return trim($contextInfo) . "\n\n" .
               "VALIDATION TASK:\n{$customPrompt}\n\n" .
               "Respond with ONLY this JSON format:\n" .
               '{"passed": true/false, "reason": "specific reason here", "confidence": 0.0-1.0}' . "\n\n" .
               "Remember: Be conservative. If you're not certain, reject the photo with a clear reason.";
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $systemPrompt, string $userPrompt, array $imageContent): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $userPrompt
                        ],
                        $imageContent
                    ]
                ]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new Exception("OpenAI API error {$response->status()}: " . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (empty($content)) {
            throw new Exception('Empty response from OpenAI');
        }

        return $content;
    }

    /**
     * Parse AI response and extract validation result
     */
    private function parseAIResponse(string $response): array
    {
        // Try to extract JSON from response
        $json = $this->extractJsonFromResponse($response);

        if (empty($json)) {
            return [
                'passed' => false,
                'reason' => 'AI returned invalid response format',
                'confidence' => 0.0,
                'raw_response' => $response
            ];
        }

        // Validate required fields
        $passed = isset($json['passed']) ? (bool) $json['passed'] : false;
        $reason = isset($json['reason']) ? (string) $json['reason'] : 'No reason provided';
        $confidence = isset($json['confidence']) ? (float) $json['confidence'] : 0.5;

        // Clamp confidence to 0-1 range
        $confidence = max(0.0, min(1.0, $confidence));

        return [
            'passed' => $passed,
            'reason' => $reason,
            'confidence' => $confidence,
            'raw_response' => $response
        ];
    }

    /**
     * Extract JSON from AI response (handles various formats)
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        $response = trim($response);

        // Try direct JSON decode
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        // Try to find JSON in text using regex
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    /**
     * Check if file is PDF
     */
    private function isPdfFile(string $path): bool
    {
        if (str_ends_with(strtolower($path), '.pdf')) {
            return true;
        }

        if (str_starts_with($path, 'http')) {
            return str_contains(strtolower($path), '.pdf');
        }

        if (file_exists($path)) {
            $mime = mime_content_type($path);
            return $mime === 'application/pdf';
        }

        return false;
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->apiKey)) {
                return ['success' => false, 'message' => 'API key not configured'];
            }

            // Simple test with minimal request
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [['role' => 'user', 'content' => 'Test']],
                'max_tokens' => 5
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Connection OK' : 'Connection failed',
                'model' => $this->model
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
