<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Crypto\Contracts\MarketDataGateway;
use App\Domain\Crypto\Contracts\MonitoredCoinRepository;
use App\Jobs\IngestMarketTickJob;
use Illuminate\Console\Command;

final class PollMarketSnapshotsCommand extends Command
{
    protected $signature = 'crypto:market-poll
        {--interval=10 : Seconds to wait between ingestion bursts}
        {--once : Poll a single burst and exit}';

    protected $description = 'Pull spot prices via the configured gateway and enqueue lightweight market ingestion jobs';

    public function handle(MonitoredCoinRepository $coins, MarketDataGateway $gateway): int
    {
        if ((bool) $this->option('once')) {
            return $this->ingestBurst($coins, $gateway) ? static::SUCCESS : static::FAILURE;
        }

        $intervalSeconds = max(2, (int) $this->option('interval'));
        $this->info(sprintf('Polling every %ds (Ctrl+C to stop).', $intervalSeconds));

        while (true) {
            try {
                if (! $this->ingestBurst($coins, $gateway)) {
                    $this->error('Burst failed partially; backing off briefly.');
                    sleep(min($intervalSeconds, 15));
                    continue;
                }
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }

            sleep($intervalSeconds);
        }
    }

    private function ingestBurst(MonitoredCoinRepository $coins, MarketDataGateway $gateway): bool
    {
        $activeCoins = $coins->active();

        if ($activeCoins === []) {
            $this->comment('No active monitored coins.');

            return true;
        }

        $symbols = [];

        foreach ($activeCoins as $coin) {
            $sym = strtoupper(trim($coin->symbolNormalized()));
            if ($sym !== '') {
                $symbols[] = $sym;
            }
        }

        $quotes = $gateway->snapshotSpotPrices(array_values(array_unique($symbols)));

        $ok = true;

        foreach ($activeCoins as $coin) {
            $symKey = strtoupper(trim($coin->symbolNormalized()));
            $priceMoney = $quotes[$symKey] ?? null;

            if ($priceMoney === null) {
                $ok = false;
                $this->warn(sprintf('Skipping %s: missing quote', $symKey));

                continue;
            }

            IngestMarketTickJob::dispatch(
                $coin->id,
                $coin->symbolNormalized(),
                $priceMoney->amountDecimal(),
            );
        }

        return $ok;
    }
}
