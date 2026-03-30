<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\GridRange;
use Google\Service\Sheets\UpdateCellsRequest;
use Google\Service\Sheets\RowData;
use Google\Service\Sheets\RepeatCellRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GoogleSheetService
{
    private ?Sheets $service = null;
    private ?GoogleClient $client = null;
    private bool $initialized = false;
    private ?Exception $initializationError = null;

    /**
     * Lazy initialization with error handling
     */
    private function initialize(): bool
    {
        if ($this->initialized) {
            return $this->service !== null;
        }

        $this->initialized = true;

        try {
            $this->client = new GoogleClient();
            $this->client->setApplicationName('AERGAS');
            $this->client->setScopes([Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');

            $this->authenticateClient($this->client);
            $this->service = new Sheets($this->client);

            Log::info('Google Sheets service initialized successfully');
            return true;

        } catch (Exception $e) {
            $this->initializationError = $e;
            Log::error('Google Sheets service initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return $this->initialize();
    }

    /**
     * Get initialization error if any
     */
    public function getError(): ?string
    {
        return $this->initializationError?->getMessage();
    }

    private function authenticateClient(GoogleClient $client): void
    {
        // Use Google Sheets specific credentials path to ensure no conflict with Drive OAuth
        $saJsonPath = config('services.google_sheets.credentials_path');

        if ($saJsonPath) {
            $jsonPath = $this->resolveServiceAccountPath($saJsonPath);
            if ($jsonPath && file_exists($jsonPath)) {
                $client->setAuthConfig($jsonPath);
                Log::info('Google Sheets authenticated with service account');
                return;
            }
        }

        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if ($clientId && $clientSecret && $refreshToken) {
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);

            $token = $this->refreshTokenWithRetry($client, $refreshToken);
            $client->setAccessToken($token);
            Log::info('Google Sheets authenticated with OAuth');
            return;
        }

        throw new Exception('Missing Google credentials. Please configure service account JSON in services.php (google_sheets.credentials_path) or OAuth credentials.');
    }

    private function resolveServiceAccountPath(string $path): ?string
    {
        $possiblePaths = [
            $path,
            storage_path($path),
            storage_path('app/' . $path),
            base_path($path),
        ];

        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function refreshTokenWithRetry(GoogleClient $client, string $refreshToken, int $maxRetries = 3): array
    {
        $cacheKey = 'google_sheets_access_token';

        // Check if we have a cached valid token
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
            if (time() < $cachedToken['expires_at'] - 300) { // 5 minutes buffer
                Log::info('Using cached Google Sheets access token');
                return $cachedToken;
            }
        }

        $lastException = null;

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                if (!empty($token['error'])) {
                    $error = $token['error_description'] ?? $token['error'];
                    throw new Exception('Failed to fetch access token: ' . $error);
                }

                // Add expires_at timestamp
                $token['expires_at'] = time() + ($token['expires_in'] ?? 3600);

                // Cache the token
                $cacheMinutes = floor(($token['expires_in'] ?? 3600) / 60) - 5;
                Cache::put($cacheKey, $token, now()->addMinutes(max($cacheMinutes, 30)));

                return $token;

            } catch (Exception $e) {
                $lastException = $e;
                Log::warning('Token refresh failed', ['attempt' => $i + 1, 'error' => $e->getMessage()]);

                if ($i < $maxRetries - 1) {
                    sleep(2 ** $i);
                }
            }
        }

        Cache::forget($cacheKey);
        throw $lastException;
    }

    /**
     * Write data to a specific range in a Google Sheet
     * 
     * @param string $spreadsheetId The ID of the spreadsheet
     * @param string $range The A1 notation of the range (e.g., 'Sheet1!A1')
     * @param array $values The 2D array of values to write
     * @param string $valueInputOption 'RAW' or 'USER_ENTERED'
     */
    public function writeSheet(string $spreadsheetId, string $range, array $values, string $valueInputOption = 'RAW'): void
    {
        if (!$this->initialize()) {
            throw new Exception('Google Sheets service not available: ' . $this->getError());
        }

        try {
            // Manual deep clean and null handling
            $cleanValues = [];
            foreach ($values as $row) {
                if (is_array($row) || is_object($row)) {
                    $cleanRow = [];
                    foreach ($row as $cell) {
                        // Convert null to empty string explicitly
                        $cleanRow[] = $cell ?? '';
                    }
                    $cleanValues[] = $cleanRow;
                }
            }
            $values = $cleanValues;

            $body = new ValueRange();
            $body->setValues($values);

            $params = [
                'valueInputOption' => $valueInputOption
            ];

            $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            Log::info('Written to Google Sheet', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'rows' => count($values)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to write to Google Sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $spreadsheetId
            ]);
            throw new Exception('Gagal menulis ke Google Sheet: ' . $e->getMessage());
        }
    }

    /**
     * Append data to a sheet (useful for chunking)
     */
    public function appendSheet(string $spreadsheetId, string $range, array $values): void
    {
        if (!$this->initialize()) {
            throw new Exception('Google Sheets service not available: ' . $this->getError());
        }

        try {
            // Manual deep clean and null handling
            $cleanValues = [];
            foreach ($values as $row) {
                if (is_array($row) || is_object($row)) {
                    $cleanRow = [];
                    foreach ($row as $cell) {
                        // Convert null to empty string explicitly
                        $cleanRow[] = $cell ?? '';
                    }
                    $cleanValues[] = $cleanRow;
                }
            }
            $values = $cleanValues;

            // Debug log
            if (!empty($values)) {
                Log::info('Sync Payload Sample (Row 0)', ['json_sample' => json_encode($values[0])]);
            }

            // Creating ValueRange object might be causing serialization issues with some library versions.
            // Sending raw array often works better as the client handles it.
            $body = new ValueRange(['values' => $values]);

            $params = [
                'valueInputOption' => 'USER_ENTERED', // Parses numbers, dates, formulas automatically
                'insertDataOption' => 'OVERWRITE'    // Prevents shifting existing formulas down
            ];

            $this->service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

            Log::info('Appended to Google Sheet', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'rows' => count($values)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to append to Google Sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $spreadsheetId,
                'range' => $range
            ]);
            throw new Exception('Gagal menambahkan data ke Google Sheet: ' . $e->getMessage());
        }
    }

    /**
     * Get Sheet ID (GID) by Sheet Name
     */
    public function getSheetId(string $spreadsheetId, string $sheetName): ?int
    {
        if (!$this->initialize()) {
            return null;
        }

        try {
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    return $sheet->getProperties()->getSheetId();
                }
            }
            return null;
        } catch (Exception $e) {
            Log::error('Failed to get Sheet ID', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $spreadsheetId,
                'sheetName' => $sheetName
            ]);
            return null;
        }
    }

    /**
     * Clear specific formatting (Background & Bold) starting from a specific row
     * Preserves Alignment, Borders, Data Validation etc.
     */
    public function clearFormatting(string $spreadsheetId, int $sheetId, int $startRowIndex = 0): void
    {
        if (!$this->initialize()) {
            throw new Exception('Google Sheets service not available: ' . $this->getError());
        }

        try {
            // We use RepeatCellRequest to strictly target specific fields

            $requests = [
                // 1. Reset Background Color (to default/none)
                new Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $startRowIndex,
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                // Empty object implies default
                            ]
                        ],
                        'fields' => 'userEnteredFormat.backgroundColor'
                    ]
                ]),
                // 2. Reset Bold (set to false)
                new Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $startRowIndex,
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'textFormat' => ['bold' => false]
                            ]
                        ],
                        'fields' => 'userEnteredFormat.textFormat.bold'
                    ]
                ])
            ];

            $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);

            $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

            // Log skipped for brevity

        } catch (Exception $e) {
            Log::error('Failed to clear formatting', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $spreadsheetId,
                'sheetId' => $sheetId
            ]);
        }
    }

    /**
     * Clear content of a specific range or sheet
     */
    public function clearSheet(string $spreadsheetId, string $range): void
    {
        if (!$this->initialize()) {
            throw new Exception('Google Sheets service not available: ' . $this->getError());
        }

        try {
            $requestBody = new ClearValuesRequest();
            $this->service->spreadsheets_values->clear($spreadsheetId, $range, $requestBody);

            Log::info('Cleared Google Sheet range', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range
            ]);

        } catch (Exception $e) {
            Log::error('Failed to clear Google Sheet', [
                'error' => $e->getMessage(),
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'client_email' => $this->getServiceAccountEmail() // Log ensuring we are using the right account
            ]);
            // Pass the original Google API error message for better clarity
            throw $e;
        }
    }

    /**
     * Get the service account email
     */
    public function getServiceAccountEmail(): ?string
    {
        if (!$this->initialize()) {
            return null;
        }

        try {
            // If using service account, get email from JSON
            $saJsonPath = config('services.google_sheets.credentials_path');
            if ($saJsonPath) {
                $jsonPath = $this->resolveServiceAccountPath($saJsonPath);
                if ($jsonPath && file_exists($jsonPath)) {
                    $json = json_decode(file_get_contents($jsonPath), true);
                    return $json['client_email'] ?? null;
                }
            }

            // If using OAuth, might need to call user info endpoint, but skipping for now as SA is preferred
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
