<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use App\Domain\Crypto\Enums\NotificationChannel;

interface AlertNotificationChannelStrategy
{
    public function channel(): NotificationChannel;

    /** @param array<string, mixed> $channelPayload merged with defaults */
    public function sendAlert(string $headline, string $bodyMarkdown, array $channelPayload): void;
}
