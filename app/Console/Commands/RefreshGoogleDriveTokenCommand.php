<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Illuminate\Console\Command;
use Exception;

class RefreshGoogleDriveTokenCommand extends Command
{
    protected $signature = 'google-drive:refresh-token';
    protected $description = 'Refresh Google Drive access token';

    public function handle()
    {
        $this->info('Attempting to refresh Google Drive token...');
        $this->newLine();

        try {
            $client = new GoogleClient();
            $client->setClientId(config('services.google_drive.client_id'));
            $client->setClientSecret(config('services.google_drive.client_secret'));

            $refreshToken = config('services.google_drive.refresh_token');

            if (!$refreshToken) {
                $this->error('No refresh token found in configuration');
                return 1;
            }

            $this->info('Using refresh token: ' . substr($refreshToken, 0, 20) . '...');

            // Attempt to fetch access token
            $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($accessToken['error'])) {
                $this->error('Token refresh failed:');
                $this->error('Error: ' . $accessToken['error']);
                if (isset($accessToken['error_description'])) {
                    $this->error('Description: ' . $accessToken['error_description']);
                }

                $this->newLine();
                $this->warn('This usually means:');
                $this->warn('1. The refresh token has been revoked');
                $this->warn('2. The OAuth app credentials have changed');
                $this->warn('3. The token has expired (after 6 months of inactivity)');
                $this->newLine();
                $this->info('You may need to re-authorize the application at:');
                $this->info('https://developers.google.com/oauthplayground/');

                return 1;
            }

            $this->line('✓ Token refreshed successfully!');
            $this->line('Access Token: ' . substr($accessToken['access_token'], 0, 20) . '...');
            $this->line('Token Type: ' . ($accessToken['token_type'] ?? 'Bearer'));
            $this->line('Expires In: ' . ($accessToken['expires_in'] ?? 'Unknown') . ' seconds');

            if (isset($accessToken['refresh_token'])) {
                $this->warn('New refresh token received - you should update your .env file:');
                $this->warn('GOOGLE_DRIVE_REFRESH_TOKEN=' . $accessToken['refresh_token']);
            }

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to refresh token:');
            $this->error($e->getMessage());

            $this->newLine();
            $this->info('Debug information:');
            $this->info('Client ID: ' . config('services.google_drive.client_id'));
            $this->info('Client Secret: ' . (config('services.google_drive.client_secret') ? 'SET' : 'NOT SET'));
            $this->info('Refresh Token: ' . (config('services.google_drive.refresh_token') ? 'SET' : 'NOT SET'));

            return 1;
        }
    }
}
