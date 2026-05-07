<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifier;

use App\Domain\Crypto\Contracts\TriggeredAlertNotifier;
use App\Domain\Crypto\Entities\AlertRule;
use App\Domain\Crypto\ValueObjects\Money;
use App\Jobs\SendTriggeredAlertNotificationJob;

final readonly class DispatchAlertNotificationsThroughQueue implements TriggeredAlertNotifier
{
    public function notify(AlertRule $rule, Money $currentPrice): void
    {
        SendTriggeredAlertNotificationJob::dispatch($rule->id, $currentPrice->amountDecimal())
            ->onQueue('notifications');
    }
}
