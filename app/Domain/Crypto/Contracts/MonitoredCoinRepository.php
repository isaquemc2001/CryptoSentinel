<?php

declare(strict_types=1);

namespace App\Domain\Crypto\Contracts;

use App\Domain\Crypto\Entities\MonitoredCoin;

interface MonitoredCoinRepository
{
    /** @return list<MonitoredCoin> */
    public function active(): array;

    public function findById(int $id): ?MonitoredCoin;

    public function create(
        string $symbol,
        string $baseAsset,
        string $quoteAsset,
        ?string $label,
        bool $active,
    ): MonitoredCoin;

    public function update(int $id, ?string $label, ?bool $active): MonitoredCoin;

    public function delete(int $id): void;
}
