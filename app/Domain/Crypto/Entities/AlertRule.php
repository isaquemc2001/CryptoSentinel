<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Entities;

use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;
use App\Domain\Crypto\ValueObjects\Money;
use App\Domain\Crypto\ValueObjects\Percentage;

readonly final class AlertRule
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $monitoredCoinId,
        public AlertTriggerType $triggerType,
        public ?Money $thresholdPrice,
        public ?Percentage $thresholdPercent,
        public ?int $windowMinutes,
        public NotificationChannel $notifyChannel,
        /** @var array<string, mixed> */
        public array $notifyPayload,
        public bool $active,
    ) {
    }
}
