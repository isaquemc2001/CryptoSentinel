<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Crypto\Contracts\AlertRuleRepository;
use App\Domain\Crypto\Contracts\PriceRollingWindow;
use App\Domain\Crypto\Contracts\TriggeredAlertNotifier;
use App\Domain\Crypto\Entities\AlertRule;
use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\ValueObjects\Money;
use App\Domain\Crypto\ValueObjects\Percentage;
use Carbon\CarbonInterface;
use RuntimeException;

final class EvaluatePriceAlertsService
{
    public function __construct(
        private readonly AlertRuleRepository $rules,
        private readonly PriceRollingWindow $priceWindow,
        private readonly TriggeredAlertNotifier $notifier,
    ) {
    }

    public function evaluate(
        string $normalizedSymbol,
        Money $latestPrice,
        CarbonInterface $at,
        int $monitoredCoinId,
    ): void {
        $rules = $this->rules->activeForCoinId($monitoredCoinId);

        foreach ($rules as $rule) {
            if ($this->fires($rule, $latestPrice, $at, $normalizedSymbol)) {
                $this->notifier->notify($rule, $latestPrice);
            }
        }
    }

    private function fires(
        AlertRule $rule,
        Money $latest,
        CarbonInterface $at,
        string $normalizedSymbol,
    ): bool {
        return match ($rule->triggerType) {
            AlertTriggerType::PriceAtOrAbove => $this->priceAtOrAbove($rule, $latest),
            AlertTriggerType::PriceAtOrBelow => $this->priceAtOrBelow($rule, $latest),
            AlertTriggerType::PercentChangeInWindow => $this->percentWindow($rule, $latest, $at, $normalizedSymbol),
        };
    }

    private function priceAtOrAbove(AlertRule $rule, Money $latest): bool
    {
        $target = $rule->thresholdPrice ?? throw new RuntimeException('threshold_price missing for price alert');

        return $latest->greaterOrEqualThan($target);
    }

    private function priceAtOrBelow(AlertRule $rule, Money $latest): bool
    {
        $target = $rule->thresholdPrice ?? throw new RuntimeException('threshold_price missing for price alert');

        return $latest->lessOrEqualThan($target);
    }

    private function percentWindow(
        AlertRule $rule,
        Money $latest,
        CarbonInterface $at,
        string $normalizedSymbol,
    ): bool {
        $percent = $rule->thresholdPercent
            ?? throw new RuntimeException('threshold_percent missing for percent alert');
        $minutes = $rule->windowMinutes
            ?? throw new RuntimeException('window_minutes missing for percent alert');

        $this->priceWindow->appendSample($normalizedSymbol, $latest->amountDecimal(), $at);

        $fromPrice = $this->priceWindow->earliestPriceWithin($normalizedSymbol, $minutes, $at);
        if ($fromPrice === null) {
            return false;
        }

        $changeSigned = Percentage::fromDecimalChange($fromPrice, $latest->amountDecimal())->valueSigned();

        return $this->absGreaterOrEqual($changeSigned, $percent->magnitude());
    }

    /** @param numeric-string $value */
    /** @param numeric-string $needle */
    private function absGreaterOrEqual(string $value, string $needle): bool
    {
        $absValue = str_starts_with($value, '-') ? substr($value, 1) : ltrim(ltrim($value, '+'));

        return bccomp($absValue, $needle, 8) !== -1;
    }
}
