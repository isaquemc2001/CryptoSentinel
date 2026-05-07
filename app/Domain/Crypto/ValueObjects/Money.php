<?php

declare(strict_types=1);

namespace App\Domain\Crypto\ValueObjects;

use InvalidArgumentException;
use Stringable;

readonly final class Money implements Stringable
{
    public function __construct(
        private string $amountDecimal,
        private string $currency = 'USDT'
    ) {
        if ($this->amountDecimal === '' || ! is_numeric($this->amountDecimal)) {
            throw new InvalidArgumentException('Money amount must be a non-empty numeric string.');
        }
    }

    public function amountDecimal(): string
    {
        return $this->amountDecimal;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function greaterOrEqualThan(self $other): bool
    {
        return bccomp($this->amountDecimal, $other->amountDecimal, 18) !== -1;
    }

    public function lessOrEqualThan(self $other): bool
    {
        return bccomp($this->amountDecimal, $other->amountDecimal, 18) !== 1;
    }

    public function __toString(): string
    {
        return $this->amountDecimal.' '.$this->currency;
    }
}
