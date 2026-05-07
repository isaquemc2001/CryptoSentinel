<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use App\Domain\Crypto\ValueObjects\Money;

interface MarketDataGateway
{
    /** @param  list<string>  $symbolsNormalized */
    /** @return array<string, Money> keyed by normalized symbol */
    public function snapshotSpotPrices(array $symbolsNormalized): array;
}
