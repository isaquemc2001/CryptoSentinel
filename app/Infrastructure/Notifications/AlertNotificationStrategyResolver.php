<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Crypto\Contracts\AlertNotificationChannelStrategy;
use App\Domain\Crypto\Enums\NotificationChannel;
use InvalidArgumentException;

final readonly class AlertNotificationStrategyResolver
{
    /**
     * @param iterable<AlertNotificationChannelStrategy> $strategies
     */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    public function resolve(NotificationChannel $channel): AlertNotificationChannelStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->channel() === $channel) {
                return $strategy;
            }
        }

        throw new InvalidArgumentException(sprintf('No notification strategy for channel %s', $channel->value));
    }
}
