<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreErrorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:65000'],
            'server_name' => ['nullable', 'string', 'max:255'],
            'log_source' => ['sometimes', 'string', Rule::in(config('autofix.valid_log_sources', ['server', 'application']))],
        ];
    }
}
