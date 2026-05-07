<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Strategies;

use App\Domain\Crypto\Contracts\AlertNotificationChannelStrategy;
use App\Domain\Crypto\Enums\NotificationChannel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class TelegramNotificationStrategy implements AlertNotificationChannelStrategy
{
    public function __construct(
        private ?string $botToken,
        private int $timeoutSeconds,
    ) {
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Telegram;
    }

    public function sendAlert(string $headline, string $bodyMarkdown, array $channelPayload): void
    {
        $chatId = $channelPayload['chat_id'] ?? null;
        if (! is_scalar($chatId) || $chatId === '') {
            throw new RuntimeException('telegram chat_id missing in notify_payload.');
        }

        if ($this->botToken === null || $this->botToken === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        $text = "**{$headline}**\n\n".$bodyMarkdown;

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', (string) $this->botToken);

        $response = Http::asForm()
            ->timeout($this->timeoutSeconds)
            ->retry(3, 300)
            ->post($endpoint, [
                'chat_id' => (string) $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('telegram send failed [%s]', $response->status()));
        }
    }
}
