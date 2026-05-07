<?php

declare(strict_types=1);

namespace App\Domain\Crypto\ValueObjects;

use InvalidArgumentException;
use Stringable;

readonly final class Percentage implements Stringable
{
    public function __construct(
        private string $valueSigned
    ) {
        if (! is_numeric($this->valueSigned)) {
            throw new InvalidArgumentException('Percentage must be numeric.');
        }
    }

    public function valueSigned(): string
    {
        return $this->valueSigned;
    }

    /** Absolute magnitude for threshold comparisons against unsigned delta. */
    public function magnitude(): string
    {
        return ltrim(ltrim(ltrim((string) $this->valueSigned, '+'), '-'), '-');
    }

    public static function fromDecimalChange(string $fromPrice, string $toPrice): self
    {
        if (bccomp($fromPrice, '0', 18) === 0) {
            return new self('0');
        }

        $delta = bcmul(bcdiv(bcsub($toPrice, $fromPrice, 18), $fromPrice, 18), '100', 8);

        return new self($delta);
    }

    public function __toString(): string
    {
        return $this->valueSigned.'%';
    }
}
