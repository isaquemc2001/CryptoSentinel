<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use App\Domain\Crypto\Entities\AlertRule;
use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;

interface AlertRuleRepository
{
    /** @return list<AlertRule> */
    public function list(int $userId, ?int $monitoredCoinId = null): array;

    /** @return list<AlertRule> */
    public function listForCoinSymbol(string $normalizedSymbol): array;

    /** @return list<AlertRule> active only */
    public function activeForCoinId(int $monitoredCoinId): array;

    public function find(int $userId, int $id): ?AlertRule;

    public function create(
        int $user_id,
        int $monitoredCoinId,
        AlertTriggerType $triggerType,
        ?string $thresholdPrice,
        ?string $thresholdPercent,
        ?int $windowMinutes,
        NotificationChannel $channel,
        array $payload,
        bool $active,
    ): AlertRule;

    public function update(
        int $id,
        int $userId,
        ?AlertTriggerType $triggerType,
        ?string $thresholdPrice,
        ?string $thresholdPercent,
        ?int $windowMinutes,
        ?NotificationChannel $channel,
        ?array $payload,
        ?bool $active,
    ): AlertRule;

    public function delete(int $userId, int $id): void;
}
