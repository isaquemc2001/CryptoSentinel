<?php

declare(strict_types=1);

return [
    'alert_rules_cache_ttl' => (int) env('CRYPTOSENT_ALERT_RULES_CACHE_TTL', 60),
    'price_window_max_retention_minutes' => (int) env('CRYPTOSENT_PRICE_WINDOW_RETENTION_MIN', 2880),

    /*
    | Cool-down after a notification is successfully dispatched for a rule.
    | Prevents Telegram flooding while thresholds remain breached between polls.
    */
    'alert_notification_cooldown_seconds' => (int) env('CRYPTOSENT_ALERT_COOLDOWN_SECONDS', 600),

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'http_timeout_seconds' => 12,
    ],
];
