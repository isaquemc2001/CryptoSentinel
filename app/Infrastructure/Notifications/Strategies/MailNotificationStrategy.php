<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Strategies;

use App\Domain\Crypto\Contracts\AlertNotificationChannelStrategy;
use App\Domain\Crypto\Enums\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final readonly class MailNotificationStrategy implements AlertNotificationChannelStrategy
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::Email;
    }

    public function sendAlert(string $headline, string $bodyMarkdown, array $channelPayload): void
    {
        $to = $channelPayload['to'] ?? null;
        if (! is_string($to) || $to === '') {
            throw new RuntimeException('Email notify_payload.to is required.');
        }

        Mail::raw($bodyMarkdown, static function (\Illuminate\Mail\Message $message) use ($to, $headline): void {
            $message->to($to)
                ->subject($headline);
        });
    }
}
