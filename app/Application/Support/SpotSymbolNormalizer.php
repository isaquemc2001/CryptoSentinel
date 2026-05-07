<?php

declare(strict_types=1);

namespace App\Application\Support;

use InvalidArgumentException;

final readonly class SpotSymbolNormalizer
{
    /**
     * @return array{0: string, 1: string} base, quote
     */
    public static function explode(string $symbol, string $defaultQuoteAsset = 'USDT'): array
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', $symbol) ?? '');
        $quote = strtoupper(trim($defaultQuoteAsset));

        if ($normalized === '') {
            throw new InvalidArgumentException('Symbol cannot be empty.');
        }

        if (! str_ends_with($normalized, $quote)) {
            throw new InvalidArgumentException(sprintf('Symbol %s must end with quote asset %s.', $normalized, $quote));
        }

        $base = substr($normalized, 0, -strlen($quote));
        if ($base === '') {
            throw new InvalidArgumentException('Invalid spot symbol.');
        }

        return [$base, $quote];
    }

    /** Normalized consolidated pair symbol (BASEQUOTE). */
    public static function toNormalizedPair(string $symbol, string $defaultQuoteAsset = 'USDT'): string
    {
        [$base, $quote] = self::explode($symbol, $defaultQuoteAsset);

        return $base.$quote;
    }
}
