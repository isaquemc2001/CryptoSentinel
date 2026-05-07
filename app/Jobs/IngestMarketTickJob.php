<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Crypto\Events\MarketTickRecorded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IngestMarketTickJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $monitoredCoinId,
        public readonly string $symbolNormalized,
        public readonly string $priceDecimal,
    ) {
        $this->onConnection('redis');
        $this->onQueue('market-data');
    }

    public function handle(): void
    {
        event(new MarketTickRecorded(
            monitoredCoinId: $this->monitoredCoinId,
            symbolNormalized: $this->symbolNormalized,
            priceDecimal: $this->priceDecimal,
            recordedAt: now()->toImmutable(),
        ));
    }
}
