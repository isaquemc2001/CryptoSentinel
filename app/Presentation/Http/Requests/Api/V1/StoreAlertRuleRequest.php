<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests\Api\V1;

use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'monitored_coin_id' => ['required', 'integer', 'exists:monitored_coins,id'],
            'trigger_type' => ['required', Rule::enum(AlertTriggerType::class)],
            'threshold_price' => ['sometimes', 'nullable', 'string'],
            'threshold_percent' => ['sometimes', 'nullable', 'string'],
            'window_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:44640'],
            'notify_channel' => ['required', Rule::enum(NotificationChannel::class)],
            'notify_payload' => ['sometimes', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->failed()) {
                return;
            }

            $trigger = AlertTriggerType::tryFrom((string) $this->input('trigger_type'));

            if (! $trigger instanceof AlertTriggerType) {
                return;
            }

            if ($trigger === AlertTriggerType::PriceAtOrAbove || $trigger === AlertTriggerType::PriceAtOrBelow) {
                $price = trim((string) $this->input('threshold_price', ''));
                if ($price === '' || ! is_numeric($price)) {
                    $validator->errors()->add('threshold_price', 'threshold_price is required for price alerts.');
                }
            }

            if ($trigger === AlertTriggerType::PercentChangeInWindow) {
                $pct = trim((string) $this->input('threshold_percent', ''));

                if ($pct === '' || ! is_numeric($pct)) {
                    $validator->errors()->add('threshold_percent', 'threshold_percent is required for percent alerts.');
                }

                if ($this->input('window_minutes') === null) {
                    $validator->errors()->add('window_minutes', 'window_minutes is required for percent alerts.');
                }
            }
        });
    }
}
