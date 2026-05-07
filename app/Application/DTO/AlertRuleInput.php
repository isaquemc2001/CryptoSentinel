<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;

readonly final class AlertRuleInput
{
    /**
     * @param  array<string, mixed>  $notifyPayload
     */
    public function __construct(
        public int $monitoredCoinId,
        public AlertTriggerType $triggerType,
        public ?string $thresholdPrice,
        public ?string $thresholdPercent,
        public ?int $windowMinutes,
        public NotificationChannel $notifyChannel,
        public array $notifyPayload,
        public bool $active,
    ) {
    }
}
