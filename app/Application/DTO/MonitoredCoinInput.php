<?php

declare(strict_types=1);

namespace App\Application\DTO;

readonly final class MonitoredCoinInput
{
    public function __construct(
        public string $symbol,
        public ?string $label,
        public bool $active,
    ) {
    }
}
