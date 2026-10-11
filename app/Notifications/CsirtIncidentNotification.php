<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class CsirtIncidentNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $severity = 'warning', // critical, warning, info
        public ?string $url = null
    ) {}

    public function via($notifiable): array
    {
        if (empty(config('services.telegram-bot-api.token')) || empty(config('services.telegram-bot-api.chat_id'))) {
            return [];
        }

        return ['telegram'];
    }

    public function toTelegram($notifiable): TelegramMessage
    {
        $emoji = match ($this->severity) {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        $witaTime = now()->setTimezone('Asia/Makassar')->translatedFormat('d F Y, H:i').' WITA';

        $content = "{$emoji} <b>{$this->title}</b>\n\n";
        $content .= "{$this->message}\n\n";
        $content .= "🏛️ <b>CSIRT-SINJAIKAB • Tim Tanggap Insiden Siber Kabupaten Sinjai</b>\n";
        $content .= "<i>🕒 {$witaTime}</i>";

        $telegramMessage = TelegramMessage::create()
            ->to(config('services.telegram-bot-api.chat_id'))
            ->options(['parse_mode' => 'HTML'])
            ->content($content);

        if ($this->url) {
            $telegramMessage->button('Kunjungi Website', $this->url);
        }

        return $telegramMessage;
    }
}
