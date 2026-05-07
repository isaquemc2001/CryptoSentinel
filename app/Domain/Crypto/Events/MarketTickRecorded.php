<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Events;

use Carbon\CarbonInterface;

final readonly class MarketTickRecorded
{
    public function __construct(
        public int $monitoredCoinId,
        public string $symbolNormalized,
        public string $priceDecimal,
        public CarbonInterface $recordedAt,
    ) {
    }
}
