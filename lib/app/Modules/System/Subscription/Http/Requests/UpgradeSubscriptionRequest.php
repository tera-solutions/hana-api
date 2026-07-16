<?php

namespace App\Modules\System\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:sys_packages,id'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'billing_cycle' => ['nullable', Rule::in(['month', 'year'])],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Vui lòng chọn gói dịch vụ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'package_id' => ['description' => 'Package to subscribe to.', 'example' => 1],
            'payment_method' => ['description' => 'Payment method used.', 'example' => 'bank_transfer'],
            'billing_cycle' => ['description' => 'Billing cycle: month or year.', 'example' => 'month'],
        ];
    }
}
