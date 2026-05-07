<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Strategies;

use App\Domain\Crypto\Contracts\AlertNotificationChannelStrategy;
use App\Domain\Crypto\Enums\NotificationChannel;
use Illuminate\Support\Facades\Log;

/**
 * MVP placeholder until a concrete Web Push provider/subscription lifecycle is wired in.
 */
final readonly class LoggingWebPushNotificationStrategy implements AlertNotificationChannelStrategy
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::WebPush;
    }

    public function sendAlert(string $headline, string $bodyMarkdown, array $channelPayload): void
    {
        Log::info('[web_push_stub] '.$headline, [
            'payload_keys' => array_keys($channelPayload),
            'body' => $bodyMarkdown,
        ]);
    }
}
