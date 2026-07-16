<?php

namespace App\Modules\System\Superadmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:sys_packages,id'],
            'billing_cycle' => ['nullable', 'string', Rule::in(['month', 'year'])],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Vui lòng chọn gói dịch vụ.',
            'package_id.exists' => 'Gói dịch vụ không tồn tại.',
            'billing_cycle.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'amount.numeric' => 'Số tiền không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'package_id' => ['description' => 'Target package id.', 'example' => 2],
            'billing_cycle' => ['description' => 'month|year (defaults to the package cycle).', 'example' => 'month'],
            'payment_method' => ['description' => 'How the manual payment was collected.', 'example' => 'bank_transfer'],
            'amount' => ['description' => 'Amount charged (defaults to the package price).', 'example' => 299000],
        ];
    }
}
