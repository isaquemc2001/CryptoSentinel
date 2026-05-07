<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class AlertRuleModel extends Model
{
    protected $table = 'alert_rules';

    /** @return BelongsTo<MonitoredCoinModel, AlertRuleModel> */
    public function monitoredCoin(): BelongsTo
    {
        return $this->belongsTo(MonitoredCoinModel::class, 'monitored_coin_id');
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'monitored_coin_id',
        'trigger_type',
        'threshold_price',
        'threshold_percent',
        'window_minutes',
        'notify_channel',
        'notify_payload',
        'active',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $rule): void {
            if ($rule->uuid === null) {
                $rule->uuid = (string) Str::uuid();
            }

            $rule->notify_channel = strtolower((string) $rule->notify_channel);
        });

        static::updating(static function (self $rule): void {
            $rule->notify_channel = strtolower((string) $rule->notify_channel);
        });
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'notify_payload' => 'array',
            'threshold_price' => 'string',
            'threshold_percent' => 'string',
            'active' => 'boolean',
        ];
    }
}
