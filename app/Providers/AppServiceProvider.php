<?php

namespace App\Providers;

use App\Application\Services\EvaluatePriceAlertsService;
use App\Domain\Crypto\Contracts\AlertRuleRepository;
use App\Domain\Crypto\Contracts\MarketDataGateway;
use App\Domain\Crypto\Contracts\MonitoredCoinRepository;
use App\Domain\Crypto\Contracts\PriceRollingWindow;
use App\Domain\Crypto\Contracts\TriggeredAlertNotifier;
use App\Infrastructure\MarketData\BinanceTickerGateway;
use App\Infrastructure\MarketData\RedisPriceRollingWindow;
use App\Infrastructure\Notifications\AlertNotificationStrategyResolver;
use App\Infrastructure\Notifications\Strategies\LoggingWebPushNotificationStrategy;
use App\Infrastructure\Notifications\Strategies\MailNotificationStrategy;
use App\Infrastructure\Notifications\Strategies\TelegramNotificationStrategy;
use App\Infrastructure\Notifier\DispatchAlertNotificationsThroughQueue;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAlertRuleRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentMonitoredCoinRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MonitoredCoinRepository::class, EloquentMonitoredCoinRepository::class);
        $this->app->singleton(AlertRuleRepository::class, EloquentAlertRuleRepository::class);
        $this->app->singleton(MarketDataGateway::class, BinanceTickerGateway::class);
        $this->app->singleton(TriggeredAlertNotifier::class, DispatchAlertNotificationsThroughQueue::class);

        $this->app->singleton(PriceRollingWindow::class, function (Application $app): RedisPriceRollingWindow {
            /** @phpstan-ignore-next-line */
            $ttl = (int) config('cryptosentinel.price_window_max_retention_minutes', 2880);

            return new RedisPriceRollingWindow($ttl);
        });

        $this->app->singleton(TelegramNotificationStrategy::class, function (): TelegramNotificationStrategy {
            return new TelegramNotificationStrategy(
                config('cryptosentinel.telegram.bot_token'),
                (int) config('cryptosentinel.telegram.http_timeout_seconds', 12),
            );
        });

        $this->app->singleton(MailNotificationStrategy::class, MailNotificationStrategy::class);
        $this->app->singleton(LoggingWebPushNotificationStrategy::class, LoggingWebPushNotificationStrategy::class);

        $this->app->singleton(AlertNotificationStrategyResolver::class, function (Application $app): AlertNotificationStrategyResolver {
            return new AlertNotificationStrategyResolver([
                $app->make(TelegramNotificationStrategy::class),
                $app->make(MailNotificationStrategy::class),
                $app->make(LoggingWebPushNotificationStrategy::class),
            ]);
        });

        $this->app->singleton(EvaluatePriceAlertsService::class, EvaluatePriceAlertsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
