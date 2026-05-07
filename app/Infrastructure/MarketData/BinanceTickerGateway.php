<?php

declare(strict_types=1);

namespace App\Infrastructure\MarketData;

use App\Domain\Crypto\Contracts\MarketDataGateway;
use App\Domain\Crypto\ValueObjects\Money;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class BinanceTickerGateway implements MarketDataGateway
{
    private const string BASE_URL = 'https://api.binance.com/api/v3';

    /** @inheritdoc */
    public function snapshotSpotPrices(array $symbolsNormalized): array
    {
        /** @var array<string, Money> $out */
        $out = [];

        foreach ($symbolsNormalized as $symbol) {
            $sym = strtoupper(trim($symbol));
            $payload = Http::retry(4, 200)
                ->timeout(15)
                ->acceptJson()
                ->get(self::BASE_URL.'/ticker/price', [
                    'symbol' => $sym,
                ]);

            if (! $payload->successful()) {
                report(new RuntimeException(sprintf('Binance ticker failed (%s)', $payload->status())));

                continue;
            }

            /** @var array{symbol?: string, price?: string|string|float|int} $decoded */
            $decoded = $payload->json();

            $priceRaw = isset($decoded['price']) ? (string) $decoded['price'] : null;
            if ($priceRaw === null || ! is_numeric($priceRaw)) {
                report(new RuntimeException('Binance ticker response missing price'));

                continue;
            }

            $out[$sym] = new Money($priceRaw);
        }

        return $out;
    }
}
