<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class MonitoredCoinModel extends Model
{
    protected $table = 'monitored_coins';

    /** @use HasMany<AlertRuleModel, MonitoredCoinModel> */
    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRuleModel::class, 'monitored_coin_id');
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'symbol',
        'base_asset',
        'quote_asset',
        'label',
        'active',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $coin): void {
            if ($coin->uuid === null) {
                $coin->uuid = (string) Str::uuid();
            }

            $coin->symbol = strtoupper(trim((string) $coin->symbol));
            $coin->base_asset = strtoupper(trim((string) $coin->base_asset));
            $coin->quote_asset = strtoupper(trim((string) $coin->quote_asset));
        });

        static::updating(static function (self $coin): void {
            $coin->symbol = strtoupper(trim((string) $coin->symbol));
            $coin->base_asset = strtoupper(trim((string) $coin->base_asset));
            $coin->quote_asset = strtoupper(trim((string) $coin->quote_asset));
        });
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
