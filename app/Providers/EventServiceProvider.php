<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Listeners\QueuedBroadcastCryptoPriceTick;
use App\Application\Listeners\QueuedEvaluateAlertsOnMarketTick;
use App\Domain\Crypto\Events\MarketTickRecorded;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * Observer-style fan-out after each successful price tick ingestion (Strategy + Observer).
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        MarketTickRecorded::class => [
            QueuedEvaluateAlertsOnMarketTick::class,
            QueuedBroadcastCryptoPriceTick::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
