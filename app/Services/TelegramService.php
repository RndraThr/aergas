<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send message to Telegram
     *
     * @param string $message
     * @param string $parseMode
     * @param string|null $chatId
     * @return array|bool
     */
    public function sendMessage(string $message, string $parseMode = 'HTML', ?string $chatId = null): array|bool
    {
        try {
            $targetChatId = $chatId ?: $this->chatId;

            $response = Http::timeout(30)->post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $targetChatId,
                    'message_length' => strlen($message)
                ]);
                return $response->json();
            }

            Log::error('Failed to send Telegram message', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            return false;

        } catch (Exception $e) {
            Log::error('Telegram service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send photo rejection alert
     *
     * @param string $reffId
     * @param string $module
     * @param string $photoField
     * @param string $rejectedBy
     * @param string $reason
     * @return array|bool
     */
    public function sendPhotoRejectionAlert(
        string $reffId,
        string $module,
        string $photoField,
        string $rejectedBy,
        string $reason
    ): array|bool {
        $message = "🚨 <b>PHOTO REJECTION ALERT</b>\n\n";
        $message .= "📋 <b>Customer:</b> {$reffId}\n";
        $message .= "🔧 <b>Module:</b> " . strtoupper($module) . "\n";
        $message .= "📷 <b>Photo:</b> {$photoField}\n";
        $message .= "👤 <b>Rejected by:</b> {$rejectedBy}\n";
        $message .= "❌ <b>Reason:</b> {$reason}\n\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s T');
        $message .= "\n📱 <b>Action Required:</b> Field team needs to re-upload photo";

        return $this->sendMessage($message);
    }

    /**
     * Send module status update alert
     *
     * @param string $reffId
     * @param string $module
     * @param string $oldStatus
     * @param string $newStatus
     * @param string $user
     * @return array|bool
     */
    public function sendModuleStatusAlert(
        string $reffId,
        string $module,
        string $oldStatus,
        string $newStatus,
        string $user
    ): array|bool {
        $statusEmoji = [
            'not_started' => '⚪',
            'draft' => '📝',
            'ai_validation' => '🤖',
            'tracer_review' => '👨‍🔧',
            'cgp_review' => '👨‍💼',
            'completed' => '✅',
            'rejected' => '❌'
        ];

        $oldEmoji = $statusEmoji[$oldStatus] ?? '📊';
        $newEmoji = $statusEmoji[$newStatus] ?? '📊';

        $message = "📊 <b>MODULE STATUS UPDATE</b>\n\n";
        $message .= "📋 <b>Customer:</b> {$reffId}\n";
        $message .= "🔧 <b>Module:</b> " . strtoupper($module) . "\n";
        $message .= "📈 <b>Status:</b> {$oldEmoji} {$oldStatus} → {$newEmoji} {$newStatus}\n";
        $message .= "👤 <b>Updated by:</b> {$user}\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s T');

        return $this->sendMessage($message);
    }

    /**
     * Send daily operations report
     *
     * @param array $data
     * @return array|bool
     */
    public function sendDailyReport(array $data): array|bool
    {
        $message = "📊 <b>DAILY AERGAS REPORT</b>\n";
        $message .= "📅 <b>Date:</b> " . now()->format('Y-m-d') . "\n\n";

        $message .= "✅ <b>Completed Modules:</b> {$data['completed_modules']}\n";
        $message .= "⏳ <b>Pending Approvals:</b> {$data['pending_approvals']}\n";
        $message .= "🆕 <b>New Registrations:</b> {$data['new_registrations']}\n";
        $message .= "❌ <b>Rejections:</b> {$data['rejections']}\n";
        $message .= "📈 <b>Overall Progress:</b> {$data['completion_rate']}%\n\n";

        // Additional insights
        $message .= "💡 <b>Key Insights:</b>\n";
        if ($data['rejections'] > $data['completed_modules']) {
            $message .= "⚠️ High rejection rate detected\n";
        }
        if ($data['pending_approvals'] > 10) {
            $message .= "⚠️ High pending approvals backlog\n";
        }
        if ($data['completion_rate'] >= 80) {
            $message .= "🎯 Excellent completion rate!\n";
        }

        return $this->sendMessage($message);
    }

    /**
     * Send SLA violation alert
     *
     * @param string $type
     * @param string $reffId
     * @param string $photoField
     * @param int $hoursOverdue
     * @param int $slaLimit
     * @return array|bool
     */
    public function sendSlaViolationAlert(
        string $type,
        string $reffId,
        string $photoField,
        int $hoursOverdue,
        int $slaLimit
    ): array|bool {
        $message = "🚨 <b>SLA VIOLATION - " . strtoupper($type) . " REVIEW</b>\n\n";
        $message .= "📋 <b>Customer:</b> {$reffId}\n";
        $message .= "📷 <b>Photo:</b> {$photoField}\n";
        $message .= "⏰ <b>Overdue:</b> {$hoursOverdue} hours\n";
        $message .= "🎯 <b>SLA Limit:</b> {$slaLimit} hours\n";
        $message .= "📅 <b>Alert Time:</b> " . now()->format('Y-m-d H:i:s T') . "\n\n";
        $message .= "🔔 <b>Action Required:</b> Immediate review needed!";

        return $this->sendMessage($message);
    }

    /**
     * Test Telegram connection
     *
     * @return array|bool
     */
    public function testConnection(): array|bool
    {
        $message = "🧪 <b>AERGAS SYSTEM TEST</b>\n\n";
        $message .= "✅ Telegram integration is working!\n";
        $message .= "⏰ Test Time: " . now()->format('Y-m-d H:i:s T');

        return $this->sendMessage($message);
    }
}
