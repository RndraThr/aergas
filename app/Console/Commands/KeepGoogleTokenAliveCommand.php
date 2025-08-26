<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

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

            // Refresh the token
            $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($accessToken['error'])) {
                $this->error('Token refresh failed: ' . $accessToken['error']);
                Log::error('Google Drive token refresh failed', $accessToken);

                // Send notification to admin (implementasikan sesuai kebutuhan)
                // $this->notifyAdmin('Google Drive token expired');

                return 1;
            }

            $this->info('Token refreshed successfully');
            Log::info('Google Drive token kept alive successfully');

            return 0;

        } catch (Exception $e) {
            $this->error('Keep alive failed: ' . $e->getMessage());
            Log::error('Google Drive keep alive failed', ['error' => $e->getMessage()]);

            return 1;
        }
    }
}
