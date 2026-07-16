<?php

namespace App\Modules\System\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['string', 'boolean', 'number', 'json'])],
            'group' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'value' => ['description' => 'Setting value (stringified).', 'example' => 'false'],
            'type' => ['description' => 'Value type: string, boolean, number, json.', 'example' => 'boolean'],
            'group' => ['description' => 'Setting group.', 'example' => 'notification'],
            'label' => ['description' => 'Display label.', 'example' => 'Thông báo tin nhắn'],
        ];
    }
}
