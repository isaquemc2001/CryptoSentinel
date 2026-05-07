<?php

declare(strict_types=1);

namespace App\Infrastructure\Broadcasting;

use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Streams spot-like ticks toward Laravel Reverb / Pusher compatible clients on public channels.
 */
final class CryptoPriceUpdatedBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $broadcastQueue = 'market-data';

    public function __construct(
        public readonly string $symbolNormalized,
        public readonly string $priceDecimal,
        public readonly CarbonInterface $receivedAt,
    ) {
        //
    }

    /** @return list<Channel> */
    public function broadcastOn(): array
    {
        $slug = strtolower($this->symbolNormalized);

        return [new Channel('crypto.prices.'.$slug)];
    }

    public function broadcastAs(): string
    {
        return 'SpotPriceUpdated';
    }

    /** @return array<string, string> */
    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->symbolNormalized,
            'price' => $this->priceDecimal,
            'received_at' => $this->receivedAt->toIso8601String(),
        ];
    }
}
