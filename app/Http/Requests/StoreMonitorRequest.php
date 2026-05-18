<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'url:http,https',
                Rule::unique('monitors', 'url'),
            ],
            'check_interval' => ['nullable', 'integer', 'min:1', 'max:60'],
            'threshold' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
