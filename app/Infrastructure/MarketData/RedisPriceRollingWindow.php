<?php

declare(strict_types=1);

namespace App\Infrastructure\MarketData;

use App\Domain\Crypto\Contracts\PriceRollingWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final readonly class RedisPriceRollingWindow implements PriceRollingWindow
{
    public function __construct(
        private int $maxRetentionMinutes,
    ) {
    }

    public function appendSample(string $symbolNormalized, string $priceDecimal, CarbonInterface $at): void
    {
        $symbol = strtoupper(trim($symbolNormalized));
        $key = $this->key($symbol);
        $score = sprintf('%.f', microtime(true) * 1000);

        /** @phpstan-ignore-next-line redundant safe json */
        $member = json_encode(
            ['p' => $priceDecimal, 't' => $score],
            JSON_THROW_ON_ERROR,
        );

        try {
            Redis::zadd($key, [$member => $score]);

            $minScore = (($at->unix() * 1000) - (($this->maxRetentionMinutes + 1) * 60 * 1000));
            Redis::zremrangebyscore($key, '-inf', (string) (($minScore - 1)));

            Redis::expire($key, (($this->maxRetentionMinutes + 5) * 60));
        } catch (\Throwable $e) {
            Log::error('cryptosentinel.redis_price_window.failed', ['error' => $e->getMessage()]);
        }
    }

    public function earliestPriceWithin(string $symbolNormalized, int $minutes, CarbonInterface $now): ?string
    {
        $symbol = strtoupper(trim($symbolNormalized));
        $key = $this->key($symbol);
        $fromMs = (($now->copy()->subMinutes($minutes))->unix() * 1000);

        try {
            /** @var array<int|string, string|false>|false $rows */
            $rows = Redis::zrangebyscore($key, (string) $fromMs, '+inf', [
                'limit' => ['offset' => 0, 'count' => 1],
            ]);

            if (! is_array($rows) || $rows === []) {
                return null;
            }

            $firstMember = reset($rows);
            if ($firstMember === false) {
                return null;
            }

            /** @var array{p: non-falsy-string} $decoded */
            $decoded = json_decode((string) $firstMember, true, 512, JSON_THROW_ON_ERROR);

            return is_string($decoded['p'] ?? null) ? $decoded['p'] : null;
        } catch (\Throwable $e) {
            Log::error('cryptosentinel.redis_price_window.read_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function key(string $symbol): string
    {
        return 'cryptosentinel:price_hist:'.$symbol;
    }
}
