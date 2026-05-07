<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use App\Domain\Crypto\Entities\AlertRule;
use App\Domain\Crypto\ValueObjects\Money;

interface TriggeredAlertNotifier
{
    public function notify(AlertRule $rule, Money $currentPrice): void;
}
