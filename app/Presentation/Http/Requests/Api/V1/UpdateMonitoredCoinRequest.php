<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMonitoredCoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
