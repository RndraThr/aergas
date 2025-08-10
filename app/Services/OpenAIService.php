<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class OpenAIService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 1000);
        $this->temperature = config('services.openai.temperature', 0.1);
    }

    /**
     * Validate photo using OpenAI Vision
     *
     * @param string $imagePath
     * @param string $photoType
     * @param string $module
     * @return array
     */
    public function validatePhoto(string $imagePath, string $photoType, string $module): array
    {
        try {
            // Check if file exists and is readable
            if (!file_exists($imagePath) || !is_readable($imagePath)) {
                throw new Exception("Image file not found or not readable: {$imagePath}");
            }

            // Get file info
            $fileInfo = pathinfo($imagePath);
            $fileSize = filesize($imagePath);

            // Check file size (max 20MB for OpenAI)
            if ($fileSize > 20 * 1024 * 1024) {
                throw new Exception("Image file too large. Maximum size is 20MB");
            }

            // Read and encode image
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            // Validate mime type
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                throw new Exception("Unsupported image format: {$mimeType}");
            }

            $prompt = $this->getValidationPrompt($photoType, $module);

            Log::info('Starting OpenAI photo validation', [
                'photo_type' => $photoType,
                'module' => $module,
                'file_size' => $fileSize,
                'mime_type' => $mimeType
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}",
                                    'detail' => 'high'
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $content = $result['choices'][0]['message']['content'];

                Log::info('OpenAI validation completed', [
                    'photo_type' => $photoType,
                    'response_length' => strlen($content),
                    'tokens_used' => $result['usage']['total_tokens'] ?? 0
                ]);

                return $this->parseValidationResult($content, $photoType);
            }

            // Handle API errors
            $errorData = $response->json();
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'error' => $errorData,
                'photo_type' => $photoType
            ]);

            return $this->getErrorResponse('OpenAI API error: ' . ($errorData['error']['message'] ?? 'Unknown error'));

        } catch (Exception $e) {
            Log::error('OpenAI validation error', [
                'error' => $e->getMessage(),
                'photo_type' => $photoType,
                'module' => $module,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorResponse($e->getMessage());
        }
    }

    /**
     * Get validation prompt for specific photo type and module
     *
     * @param string $photoType
     * @param string $module
     * @return string
     */
    private function getValidationPrompt(string $photoType, string $module): string
    {
        $basePrompt = "You are an expert gas installation inspector. Analyze this image carefully and provide a JSON response with the following structure:
{
    \"validation_passed\": boolean,
    \"confidence\": number (0-100),
    \"detected_objects\": array of strings,
    \"rejection_reason\": string or null
}

";

        $specificPrompts = [
            'sk' => [
                'foto_berita_acara_url' => $basePrompt . "Check if this image shows a valid 'Berita Acara Pemasangan SK' (Gas Stove Installation Report). Look for: 1) Official document format, 2) Clear signatures from customer and technician, 3) Official company stamp/seal, 4) Installation details and specifications, 5) Legible text and proper documentation quality. The document should be complete and professionally presented.",

                'foto_pneumatic_sk_url' => $basePrompt . "Verify this image shows proper pneumatic testing of gas stove connection (SK). Look for: 1) Pneumatic testing equipment in use, 2) Gas pipes and connections visible, 3) Pressure testing setup with gauges, 4) Safety procedures being followed, 5) Professional testing environment. The test should appear to be conducted properly with appropriate equipment.",

                'foto_valve_krunchis_url' => $basePrompt . "Examine this image for proper gas valve/shut-off valve installation. Look for: 1) Gas valve clearly visible and accessible, 2) Proper installation position and mounting, 3) Safety compliance with proper connections, 4) Clear valve components and operation mechanism, 5) Professional installation quality. The valve should be properly installed and easily operable.",

                'foto_isometrik_sk_url' => $basePrompt . "Validate this isometric drawing/diagram for SK (gas stove) installation. Look for: 1) Technical drawing with proper isometric projection, 2) Pipe layout and routing clearly shown, 3) Measurements and dimensions included, 4) Professional drawing standards, 5) Installation specifications and details. The drawing should be technically accurate and complete."
            ],

            'sr' => [
                'foto_pneumatic_start_sr_url' => $basePrompt . "Check this image showing the START phase of SR (house connection) pneumatic test. Look for: 1) Pneumatic equipment set up at starting position, 2) Gas pipes and connection points visible, 3) Test equipment properly positioned, 4) Initial setup for pressure testing, 5) Safety protocols being observed. This should show the beginning of the pneumatic test process.",

                'foto_pneumatic_finish_sr_url' => $basePrompt . "Verify this image shows the COMPLETION of SR pneumatic test. Look for: 1) Completed test setup with results visible, 2) Pressure readings or test completion indicators, 3) Finished installation state, 4) Test equipment showing completion, 5) Professional completion of the testing process. This should demonstrate successful test completion.",

                'foto_kedalaman_url' => $basePrompt . "Examine this image to verify proper pipe installation depth. Look for: 1) Excavated area showing pipe depth, 2) Measuring tools or depth indicators visible, 3) Proper burial depth according to standards, 4) Pipe positioning in the excavation, 5) Clear evidence of appropriate installation depth. The depth should meet safety and regulatory requirements.",

                'foto_isometrik_sr_url' => $basePrompt . "Validate this SR isometric drawing with signatures. Look for: 1) Technical isometric drawing of house connection, 2) Complete signatures from all parties, 3) Official stamps and approvals, 4) Measurements and technical specifications, 5) Professional documentation quality. This should be a fully approved and signed technical document."
            ],

            'mgrt' => [
                'foto_mgrt_url' => $basePrompt . "Verify this MGRT (gas meter) installation image. Look for: 1) Gas meter device clearly visible, 2) Proper mounting and installation, 3) Serial number visible and readable, 4) Professional installation quality, 5) Appropriate positioning and access. The meter should be properly installed and easily accessible for reading.",

                'foto_pondasi_url' => $basePrompt . "Check this MGRT foundation/base installation. Look for: 1) Concrete foundation or proper base, 2) Stable and level installation surface, 3) Professional construction quality, 4) Appropriate size and positioning, 5) Proper installation standards. The foundation should provide stable and secure mounting."
            ],

            'gas_in' => [
                'ba_gas_in_url' => $basePrompt . "Examine this Gas In report document (BA Gas In). Look for: 1) Official gas commissioning document, 2) Complete gas connection and testing details, 3) Signatures from customer and technician, 4) Official stamps and approvals, 5) Proper documentation format. This should be a complete gas commissioning report.",

                'foto_bubble_test_sk_url' => $basePrompt . "Validate this bubble test on SK connection. Look for: 1) Bubble testing solution applied to connections, 2) No visible gas leaks (no bubbles), 3) Proper testing procedure being followed, 4) All connection points being tested, 5) Safety testing protocol observed. There should be no evidence of gas leaks.",

                'foto_regulator_url' => $basePrompt . "Check this gas regulator installation image. Look for: 1) Gas pressure regulator clearly visible, 2) Proper installation and connections, 3) Safety compliance with proper mounting, 4) Accessible for maintenance and operation, 5) Professional installation quality. The regulator should be properly installed and functional.",

                'foto_kompor_menyala_url' => $basePrompt . "Verify this gas stove operation with customer present. Look for: 1) Gas stove with visible flame, 2) Customer visible in the image, 3) Safe operation demonstration, 4) Proper flame characteristics, 5) Successful gas connection operation. This should show successful gas system operation with customer present."
            ],

            'jalur_pipa' => [
                'foto_kedalaman_pipa_url' => $basePrompt . "Check pipe installation depth in this image. Look for: 1) Excavated trench showing pipe depth, 2) Measuring tools or depth indicators, 3) Proper pipe burial depth, 4) Professional installation standards, 5) Safety compliance with depth requirements.",

                'foto_lowering_pipa_url' => $basePrompt . "Verify pipe lowering/installation process. Look for: 1) Pipe being lowered into position, 2) Proper installation technique, 3) Safety procedures being followed, 4) Professional installation process, 5) Appropriate equipment and methods.",

                'foto_casing_crossing_url' => $basePrompt . "Examine pipe casing at road/utility crossing. Look for: 1) Protective casing around gas pipe, 2) Proper crossing installation, 3) Safety protection measures, 4) Professional installation quality, 5) Compliance with crossing standards.",

                'foto_urugan_url' => $basePrompt . "Check backfill/urugan process after pipe installation. Look for: 1) Proper backfill material and technique, 2) Adequate coverage over pipe, 3) Professional backfill process, 4) Safety compliance, 5) Proper restoration of excavated area.",

                'foto_concrete_slab_url' => $basePrompt . "Verify concrete slab restoration. Look for: 1) Concrete slab properly installed, 2) Professional finishing quality, 3) Proper restoration of disturbed area, 4) Appropriate concrete work, 5) Site restoration completed properly.",

                'foto_marker_tape_url' => $basePrompt . "Check installation of gas line marker tape. Look for: 1) Warning tape installed above gas pipe, 2) Proper positioning and coverage, 3) Appropriate marker tape type, 4) Safety warning visibility, 5) Proper installation depth and placement."
            ],

            'penyambungan' => [
                'foto_penyambungan_url' => $basePrompt . "Verify pipe connection/joining process. Look for: 1) Professional pipe joining technique, 2) Proper fusion or connection method, 3) Quality connection work, 4) Safety procedures followed, 5) Professional installation standards.",

                'foto_name_tag_url' => $basePrompt . "Check name tag/identification at connection point. Look for: 1) Clear identification tag at joint location, 2) Readable information on tag, 3) Proper tag installation, 4) Professional labeling, 5) Appropriate identification marking."
            ]
        ];

        return $specificPrompts[$module][$photoType] ??
               $basePrompt . "Analyze this gas installation image for quality and safety compliance.";
    }

    /**
     * Parse OpenAI response and extract validation result
     *
     * @param string $content
     * @param string $photoType
     * @return array
     */
    private function parseValidationResult(string $content, string $photoType): array
    {
        try {
            // Try to find JSON in the response
            $jsonPattern = '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/';
            if (preg_match($jsonPattern, $content, $matches)) {
                $jsonString = $matches[0];
                $result = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($result['validation_passed'])) {
                    return [
                        'validation_passed' => (bool) $result['validation_passed'],
                        'confidence' => (float) ($result['confidence'] ?? 50),
                        'detected_objects' => (array) ($result['detected_objects'] ?? []),
                        'rejection_reason' => $result['validation_passed'] ? null : ($result['rejection_reason'] ?? 'Failed AI validation'),
                        'ai_response' => $content
                    ];
                }
            }

            // Fallback parsing using keywords
            $validationPassed = $this->determineValidationByKeywords($content);
            $confidence = $this->extractConfidence($content);

            return [
                'validation_passed' => $validationPassed,
                'confidence' => $confidence,
                'detected_objects' => $this->extractDetectedObjects($content),
                'rejection_reason' => $validationPassed ? null : $this->extractRejectionReason($content),
                'ai_response' => $content
            ];

        } catch (Exception $e) {
            Log::error('Failed to parse AI validation result', [
                'error' => $e->getMessage(),
                'content' => $content,
                'photo_type' => $photoType
            ]);

            return $this->getErrorResponse('Failed to parse AI response');
        }
    }

    /**
     * Determine validation result by analyzing keywords
     *
     * @param string $content
     * @return bool
     */
    private function determineValidationByKeywords(string $content): bool
    {
        $positiveKeywords = ['pass', 'valid', 'correct', 'proper', 'good', 'acceptable', 'compliant'];
        $negativeKeywords = ['fail', 'invalid', 'incorrect', 'improper', 'bad', 'unacceptable', 'non-compliant'];

        $content = strtolower($content);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveKeywords as $keyword) {
            $positiveCount += substr_count($content, $keyword);
        }

        foreach ($negativeKeywords as $keyword) {
            $negativeCount += substr_count($content, $keyword);
        }

        return $positiveCount > $negativeCount;
    }

    /**
     * Extract confidence score from content
     *
     * @param string $content
     * @return float
     */
    private function extractConfidence(string $content): float
    {
        if (preg_match('/confidence["\s:]+(\d+)/', $content, $matches)) {
            return min(100, max(0, (float) $matches[1]));
        }

        return 75.0; // Default confidence
    }

    /**
     * Extract detected objects from content
     *
     * @param string $content
     * @return array
     */
    private function extractDetectedObjects(string $content): array
    {
        $objects = [];

        // Common object keywords for gas installations
        $objectKeywords = [
            'pipe', 'valve', 'meter', 'regulator', 'stove', 'connection',
            'document', 'signature', 'stamp', 'measurement', 'tool', 'equipment'
        ];

        foreach ($objectKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $objects[] = $keyword;
            }
        }

        return array_unique($objects);
    }

    /**
     * Extract rejection reason from content
     *
     * @param string $content
     * @return string
     */
    private function extractRejectionReason(string $content): string
    {
        // Look for common rejection patterns
        if (stripos($content, 'blurry') !== false || stripos($content, 'unclear') !== false) {
            return 'Image quality is too low or blurry';
        }

        if (stripos($content, 'not visible') !== false || stripos($content, 'missing') !== false) {
            return 'Required objects or elements are not visible in the image';
        }

        if (stripos($content, 'incomplete') !== false) {
            return 'Installation appears incomplete or improper';
        }

        return 'Image does not meet validation requirements';
    }

    /**
     * Get error response format
     *
     * @param string $message
     * @return array
     */
    private function getErrorResponse(string $message): array
    {
        return [
            'validation_passed' => false,
            'confidence' => 0,
            'detected_objects' => [],
            'rejection_reason' => $message,
            'ai_response' => null
        ];
    }

    /**
     * Test OpenAI connection
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/models");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'OpenAI connection successful',
                    'available_models' => collect($response->json()['data'])
                        ->pluck('id')
                        ->filter(fn($model) => str_contains($model, 'gpt'))
                        ->values()
                        ->toArray()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to connect to OpenAI API',
                'error' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OpenAI connection error: ' . $e->getMessage()
            ];
        }
    }
}
