<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Entities;

readonly final class MonitoredCoin
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $symbol,
        public string $baseAsset,
        public string $quoteAsset,
        public ?string $label,
        public bool $active
    ) {
    }

    public function symbolNormalized(): string
    {
        return strtoupper(trim($this->symbol));
    }
}
