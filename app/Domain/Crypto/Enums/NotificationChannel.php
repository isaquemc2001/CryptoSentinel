<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Enums;

enum NotificationChannel: string
{
    case Telegram = 'telegram';
    case Email = 'email';
    case WebPush = 'web_push';
}
