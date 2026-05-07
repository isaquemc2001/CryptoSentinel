<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Crypto\Contracts\MonitoredCoinRepository;
use App\Domain\Crypto\Entities\MonitoredCoin;
use App\Infrastructure\Persistence\Eloquent\Models\MonitoredCoinModel;
use InvalidArgumentException;

final class EloquentMonitoredCoinRepository implements MonitoredCoinRepository
{
    /** @inheritDoc */
    public function active(): array
    {
        return MonitoredCoinModel::query()
            ->where('active', true)
            ->orderBy('symbol')
            ->get()
            ->map($this->toEntity(...))
            ->values()
            ->all();
    }

    public function findById(int $id): ?MonitoredCoin
    {
        $row = MonitoredCoinModel::query()->find($id);

        return $row ? $this->toEntity($row) : null;
    }

    public function create(
        string $symbol,
        string $baseAsset,
        string $quoteAsset,
        ?string $label,
        bool $active,
    ): MonitoredCoin {
        $normalized = strtoupper(trim($symbol));
        $baseUpper = strtoupper(trim($baseAsset));
        $quoteUpper = strtoupper(trim($quoteAsset));

        $expectedPair = $baseUpper.$quoteUpper;

        if ($normalized !== $expectedPair) {
            throw new InvalidArgumentException(sprintf(
                'Symbol %s must match base+quote (%s).',
                $normalized,
                $expectedPair,
            ));
        }

        /** @phpstan-ignore-next-line */
        $saved = MonitoredCoinModel::query()->create([
            'symbol' => $normalized,
            'base_asset' => $baseUpper,
            'quote_asset' => $quoteUpper,
            'label' => $label,
            'active' => $active,
        ]);

        return $this->toEntity($saved->fresh())
            ?? throw new InvalidArgumentException('Failed to persist monitored coin.');
    }

    public function update(int $id, ?string $label, ?bool $active): MonitoredCoin
    {
        $row = MonitoredCoinModel::query()->findOrFail($id);

        $data = [];

        if ($label !== null) {
            $data['label'] = $label;
        }

        if ($active !== null) {
            $data['active'] = $active;
        }

        if ($data !== []) {
            $row->update($data);
        }

        return $this->toEntity($row->fresh())
            ?? throw new InvalidArgumentException('Unexpected missing coin after update.');
    }

    public function delete(int $id): void
    {
        MonitoredCoinModel::query()->whereKey($id)->delete();
    }

    private function toEntity(MonitoredCoinModel $row): MonitoredCoin
    {
        return new MonitoredCoin(
            id: $row->id,
            uuid: $row->uuid,
            symbol: $row->symbol,
            baseAsset: $row->base_asset,
            quoteAsset: $row->quote_asset,
            label: $row->label,
            active: (bool) $row->active,
        );
    }
}
