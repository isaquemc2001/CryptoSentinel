<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use Carbon\CarbonInterface;

interface PriceRollingWindow
{
    public function appendSample(string $symbolNormalized, string $priceDecimal, CarbonInterface $at): void;

    public function earliestPriceWithin(string $symbolNormalized, int $minutes, CarbonInterface $now): ?string;
}
