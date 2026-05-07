<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Crypto\Contracts\AlertRuleRepository;
use App\Infrastructure\Notifications\AlertNotificationStrategyResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendTriggeredAlertNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    /** @var array<int, int|string> */
    public $backoff = [30, 120, 420, 1200];

    public function __construct(
        public readonly int $alertRuleId,
        public readonly string $currentPriceDecimal,
    ) {
        $this->onConnection('redis');
        $this->onQueue('notifications');
    }

    public function handle(AlertRuleRepository $rules, AlertNotificationStrategyResolver $resolver): void
    {
        $ruleEntity = $rules->find($this->alertRuleId);
        if ($ruleEntity === null) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $coolSeconds = (int) config('cryptosentinel.alert_notification_cooldown_seconds', 600);
        $coolKey = 'cryptosent:notify_cooldown:'.$ruleEntity->uuid;

        if (Cache::has($coolKey)) {
            return;
        }

        $strategy = $resolver->resolve($ruleEntity->notifyChannel);

        $subject = sprintf('CryptoSentinel • %s', $ruleEntity->notifyChannel->value);
        $body = $this->formatBody($ruleEntity->uuid, $this->currentPriceDecimal);

        try {
            $strategy->sendAlert($subject, $body, $ruleEntity->notifyPayload);
        } catch (Throwable $e) {
            Log::error('cryptosentinel.notification.strategy_failed', [
                'rule' => $ruleEntity->uuid,
                'channel' => $ruleEntity->notifyChannel->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Cache::put($coolKey, true, $coolSeconds);
    }

    private function formatBody(string $ruleUuid, string $price): string
    {
        return <<<MD
CryptoSentinel alert fired.

• Rule UUID: `{$ruleUuid}`
• Latest price snapshot: {$price}

_This dispatch is routed through Strategy pattern channels (Telegram, mail, Web Push MVP)._
MD;
    }
}
