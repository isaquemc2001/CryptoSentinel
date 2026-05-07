<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MonitoredCoinApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lists_and_deletes_monitoring_pairs(): void
    {
        $response = $this->postJson('/api/v1/monitored-coins', [
            'symbol' => 'BTCUSDT',
            'label' => 'Bitcoin',
            'active' => true,
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        self::assertIsArray($data);
        self::assertSame('BTCUSDT', $data['symbol']);

        $this->getJson('/api/v1/monitored-coins')->assertOk()->assertJsonFragment(['symbol' => 'BTCUSDT']);

        $coinId = (int) $data['id'];

        $rule = $this->postJson('/api/v1/alert-rules', [
            'monitored_coin_id' => $coinId,
            'trigger_type' => 'price_at_or_above',
            'threshold_price' => '85000',
            'notify_channel' => 'web_push',
            'notify_payload' => ['subscriber' => 'demo'],
            'active' => true,
        ]);

        $rule->assertCreated();

        $this->deleteJson('/api/v1/monitored-coins/'.$coinId)->assertNoContent();
    }
}
