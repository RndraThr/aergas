<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;
    private ?string $chatId;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId   = config('services.telegram.chat_id');
        $this->apiUrl   = rtrim((string) config('services.telegram.api_url', 'https://api.telegram.org/bot'), '/');
        $this->timeout  = (int) config('services.telegram.timeout', 30);
    }

    public function sendMessage(string $html): array|bool
    {
        if (!$this->botToken || !$this->chatId) return false;

        $url = "{$this->apiUrl}{$this->botToken}/sendMessage";
        $resp = Http::timeout($this->timeout)->asForm()->post($url, [
            'chat_id'    => $this->chatId,
            'text'       => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if (!$resp->ok()) {
            Log::warning('Telegram non-200', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }
        return $resp->json();
    }

    public function sendModuleStatusAlert(string $reffId, string $module, string $from, string $to, string $byName): void
    {
        $msg  = "📦 <b>Module Status</b>\n";
        $msg .= "Ref: <b>{$reffId}</b>\n";
        $msg .= "Module: <b>".strtoupper($module)."</b>\n";
        $msg .= "From: <code>{$from}</code> → To: <code>{$to}</code>\n";
        $msg .= "By: {$byName}\n";
        $msg .= "⏰ ".now()->format('Y-m-d H:i:s');
        $this->sendMessage($msg);
    }

    public function testConnection(): bool
    {
        try {
            $token = config('services.telegram.bot_token');
            if (!$token) return false;
            $response = Http::timeout(config('services.telegram.timeout', 30))
                ->get(config('services.telegram.api_url') . $token . '/getMe');
            return $response->ok() && ($response->json('ok') === true);
        } catch (\Throwable $e) {
            Log::warning('Telegram testConnection failed: '.$e->getMessage());
            return false;
        }
    }


    public function sendPhotoRejectionAlert(string $reffId, string $module, string $photoField, string $by, string $reason): void
    {
        $msg  = "⛔ <b>Photo Rejected</b>\n";
        $msg .= "Ref: <b>{$reffId}</b>\n";
        $msg .= "Module: <b>".strtoupper($module)."</b>\n";
        $msg .= "Field: <code>{$photoField}</code>\n";
        $msg .= "By: {$by}\n";
        $msg .= "Reason: <i>{$reason}</i>\n";
        $msg .= "⏰ ".now()->format('Y-m-d H:i:s');
        $this->sendMessage($msg);
    }
}
