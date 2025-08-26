<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\NotificationService;

class KeepGoogleTokenAliveCommand extends Command
{
    protected $signature = 'google-drive:keep-alive';
    protected $description = 'Keep Google Drive token alive by refreshing it periodically';

    public function handle()
    {
        try {
            $client = new GoogleClient();
            $client->setClientId(config('services.google_drive.client_id'));
            $client->setClientSecret(config('services.google_drive.client_secret'));

            $refreshToken = config('services.google_drive.refresh_token');
            if (!$refreshToken) {
                $this->error('No refresh token configured');
                return 1;
            }

            // Coba refresh token
            $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($accessToken['error'])) {
                // Log error dan kirim notifikasi
                Log::error('Google Drive token refresh failed', $accessToken);

                // Kirim notifikasi ke admin
                $this->notifyAdminTokenExpired($accessToken);

                return 1;
            }

            // Jika ada refresh token baru, update .env
            if (isset($accessToken['refresh_token'])) {
                $this->updateRefreshTokenInEnv($accessToken['refresh_token']);
            }

            Log::info('Google Drive token refreshed successfully');
            return 0;

        } catch (Exception $e) {
            Log::error('Google Drive keep alive failed', ['error' => $e->getMessage()]);

            // Notifikasi admin
            $this->notifyAdminTokenExpired(['error' => $e->getMessage()]);

            return 1;
        }
    }

    private function updateRefreshTokenInEnv(string $newToken)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        $pattern = '/^GOOGLE_DRIVE_REFRESH_TOKEN=.*/m';
        $replacement = 'GOOGLE_DRIVE_REFRESH_TOKEN=' . $newToken;

        $newContent = preg_replace($pattern, $replacement, $envContent);
        file_put_contents($envFile, $newContent);

        Log::info('Google Drive refresh token updated in .env');
    }

    private function notifyAdminTokenExpired(array $error)
    {
        // Kirim notifikasi ke admin
        try {
            app(NotificationService::class)->createNotification([
                'user_id' => 1, // admin user ID
                'type' => 'system_alert',
                'title' => 'Google Drive Token Expired',
                'message' => 'Google Drive refresh token expired. Manual renewal required.',
                'priority' => 'urgent',
                'data' => $error
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send token expiry notification', ['error' => $e->getMessage()]);
        }
    }
}
