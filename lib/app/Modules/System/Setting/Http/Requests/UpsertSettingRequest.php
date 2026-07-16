<?php

namespace App\Modules\System\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['string', 'boolean', 'number', 'json'])],
            'group' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'Khóa cài đặt là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'key' => ['description' => 'Setting key, unique per business.', 'example' => 'notification.new_message'],
            'value' => ['description' => 'Setting value (stringified).', 'example' => 'true'],
            'type' => ['description' => 'Value type: string, boolean, number, json.', 'example' => 'boolean'],
            'group' => ['description' => 'Setting group, e.g. notification, general, appearance.', 'example' => 'notification'],
            'label' => ['description' => 'Display label.', 'example' => 'Thông báo tin nhắn'],
        ];
    }
}
