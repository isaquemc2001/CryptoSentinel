<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Crypto\Contracts\AlertRuleRepository;
use App\Domain\Crypto\Entities\AlertRule as AlertRuleEntity;
use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;
use App\Domain\Crypto\ValueObjects\Money;
use App\Domain\Crypto\ValueObjects\Percentage;
use App\Infrastructure\Persistence\Eloquent\Models\AlertRuleModel;
use Illuminate\Support\Facades\Cache;

final class EloquentAlertRuleRepository implements AlertRuleRepository
{
    private const int ACTIVE_RULES_CACHE_TTL = 60;

    /** @inheritDoc */
    public function list(?int $monitoredCoinId = null): array
    {
        $builder = AlertRuleModel::query()->orderByDesc('id');

        if ($monitoredCoinId !== null) {
            $builder->where('monitored_coin_id', $monitoredCoinId);
        }

        return $builder->get()->map($this->toEntity(...))->values()->all();
    }

    /** @inheritDoc */
    public function listForCoinSymbol(string $normalizedSymbol): array
    {
        return AlertRuleModel::query()
            ->whereHas('monitoredCoin', static function ($query) use ($normalizedSymbol): void {
                $query->where('symbol', strtoupper($normalizedSymbol));
            })
            ->orderByDesc('id')
            ->get()
            ->map($this->toEntity(...))
            ->values()
            ->all();
    }

    /** @inheritDoc */
    public function activeForCoinId(int $monitoredCoinId): array
    {
        $ttl = config('cryptosentinel.alert_rules_cache_ttl', self::ACTIVE_RULES_CACHE_TTL);

        $key = $this->activeCacheKey($monitoredCoinId);

        /** @phpstan-ignore-next-line */
        return Cache::remember($key, (int) $ttl, function () use ($monitoredCoinId): array {
            return AlertRuleModel::query()
                ->where('monitored_coin_id', $monitoredCoinId)
                ->where('active', true)
                ->get()
                ->map($this->toEntity(...))
                ->values()
                ->all();
        });
    }

    public function find(int $id): ?AlertRuleEntity
    {
        $row = AlertRuleModel::query()->find($id);

        return $row ? $this->toEntity($row) : null;
    }

    public function create(
        int $monitoredCoinId,
        AlertTriggerType $triggerType,
        ?string $thresholdPrice,
        ?string $thresholdPercent,
        ?int $windowMinutes,
        NotificationChannel $channel,
        array $payload,
        bool $active,
    ): AlertRuleEntity {
        $this->persistValidation($triggerType, $thresholdPrice, $thresholdPercent, $windowMinutes);

        $model = AlertRuleModel::query()->create([
            'monitored_coin_id' => $monitoredCoinId,
            'trigger_type' => $triggerType->value,
            'threshold_price' => $thresholdPrice,
            'threshold_percent' => $thresholdPercent,
            'window_minutes' => $windowMinutes,
            'notify_channel' => $channel->value,
            'notify_payload' => $payload,
            'active' => $active,
        ]);

        $this->invalidateActiveRulesCache($monitoredCoinId);

        return $this->toEntity($model->fresh())
            ?? $this->toEntity($model);
    }

    public function update(
        int $id,
        ?AlertTriggerType $triggerType,
        ?string $thresholdPrice,
        ?string $thresholdPercent,
        ?int $windowMinutes,
        ?NotificationChannel $channel,
        ?array $payload,
        ?bool $active,
    ): AlertRuleEntity {
        $rule = AlertRuleModel::query()->findOrFail($id);
        $coinId = $rule->monitored_coin_id;

        if ($triggerType !== null) {
            $rule->trigger_type = $triggerType->value;
        }

        if ($thresholdPrice !== null) {
            $rule->threshold_price = $thresholdPrice;
        }

        if ($thresholdPercent !== null) {
            $rule->threshold_percent = $thresholdPercent;
        }

        if ($windowMinutes !== null) {
            $rule->window_minutes = $windowMinutes;
        }

        if ($channel !== null) {
            $rule->notify_channel = $channel->value;
        }

        if ($payload !== null) {
            $rule->notify_payload = $payload;
        }

        if ($active !== null) {
            $rule->active = $active;
        }

        $this->persistValidation(
            AlertTriggerType::from($rule->trigger_type),
            $rule->threshold_price,
            $rule->threshold_percent,
            $rule->window_minutes,
        );

        $rule->save();

        $this->invalidateActiveRulesCache((int) $coinId);

        return $this->toEntity($rule->fresh())
            ?? $this->toEntity($rule);
    }

    public function delete(int $id): void
    {
        $rule = AlertRuleModel::query()->findOrFail($id);
        $coinId = $rule->monitored_coin_id;
        $rule->delete();
        $this->invalidateActiveRulesCache((int) $coinId);
    }

    private function invalidateActiveRulesCache(int $coinId): void
    {
        Cache::forget($this->activeCacheKey($coinId));
    }

    private function activeCacheKey(int $monitoredCoinId): string
    {
        return sprintf('cryptosentinel:active_alert_rules_%d_v1', $monitoredCoinId);
    }

    private function persistValidation(
        AlertTriggerType $type,
        ?string $thresholdPrice,
        ?string $thresholdPercent,
        ?int $windowMinutes,
    ): void {
        if (in_array($type, [AlertTriggerType::PriceAtOrAbove, AlertTriggerType::PriceAtOrBelow], true)) {
            if ($thresholdPrice === null) {
                throw new \DomainException('threshold_price is required for price triggers.');
            }
        }

        if ($type === AlertTriggerType::PercentChangeInWindow) {
            if ($thresholdPercent === null || $windowMinutes === null) {
                throw new \DomainException('threshold_percent and window_minutes are required for percent window triggers.');
            }
        }
    }

    private function toEntity(AlertRuleModel $row): AlertRuleEntity
    {
        $money = isset($row->threshold_price)
            ? new Money(str_replace(',', '', $row->threshold_price), 'USDT')
            : null;

        $pct = isset($row->threshold_percent)
            ? new Percentage(str_replace(',', '', $row->threshold_percent))
            : null;

        return new AlertRuleEntity(
            id: $row->id,
            uuid: $row->uuid,
            monitoredCoinId: $row->monitored_coin_id,
            triggerType: AlertTriggerType::from((string) $row->trigger_type),
            thresholdPrice: $money,
            thresholdPercent: $pct,
            windowMinutes: $row->window_minutes,
            notifyChannel: NotificationChannel::from((string) $row->notify_channel),
            notifyPayload: $row->notify_payload ?? [],
            active: (bool) $row->active,
        );
    }
}
