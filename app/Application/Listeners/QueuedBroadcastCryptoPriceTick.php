<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Domain\Crypto\Events\MarketTickRecorded;
use App\Infrastructure\Broadcasting\CryptoPriceUpdatedBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class QueuedBroadcastCryptoPriceTick implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'redis';

    public string $queue = 'market-data';

    public function handle(MarketTickRecorded $event): void
    {
        broadcast(new CryptoPriceUpdatedBroadcast(
            $event->symbolNormalized,
            $event->priceDecimal,
            $event->recordedAt,
        ));
    }
}
