<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Enums;

enum AlertTriggerType: string
{
    case PriceAtOrAbove = 'price_at_or_above';
    case PriceAtOrBelow = 'price_at_or_below';
    case PercentChangeInWindow = 'percent_change_in_window';
}
