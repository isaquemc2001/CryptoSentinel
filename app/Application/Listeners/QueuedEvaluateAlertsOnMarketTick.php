<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Services\EvaluatePriceAlertsService;
use App\Domain\Crypto\Events\MarketTickRecorded;
use App\Domain\Crypto\ValueObjects\Money;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class QueuedEvaluateAlertsOnMarketTick implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'redis';

    public string $queue = 'alert-engine';

    public function __construct(
        private readonly EvaluatePriceAlertsService $evaluatePriceAlerts,
    ) {
    }

    public function handle(MarketTickRecorded $event): void
    {
        $this->evaluatePriceAlerts->evaluate(
            $event->symbolNormalized,
            new Money($event->priceDecimal),
            $event->recordedAt,
            $event->monitoredCoinId,
        );
    }
}
