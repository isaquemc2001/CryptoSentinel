<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMonitoredCoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', 'max:32'],
            'quote_asset' => ['sometimes', 'string', 'max:12'],
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
