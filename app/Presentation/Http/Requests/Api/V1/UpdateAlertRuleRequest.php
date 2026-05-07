<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests\Api\V1;

use App\Domain\Crypto\Enums\AlertTriggerType;
use App\Domain\Crypto\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'trigger_type' => ['sometimes', Rule::enum(AlertTriggerType::class)],
            'threshold_price' => ['sometimes', 'nullable', 'string'],
            'threshold_percent' => ['sometimes', 'nullable', 'string'],
            'window_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:44640'],
            'notify_channel' => ['sometimes', Rule::enum(NotificationChannel::class)],
            'notify_payload' => ['sometimes', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
