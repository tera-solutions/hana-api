<?php

namespace App\Modules\System\Superadmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'required', 'string', Rule::in(['month', 'year'])],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'limits' => ['nullable', 'array'],
            'limits.*' => ['nullable', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên gói.',
            'price.numeric' => 'Giá không hợp lệ.',
            'billing_cycle.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'limits.*.integer' => 'Giới hạn phải là số nguyên.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Plan name.', 'example' => 'Gói Cơ bản'],
            'description' => ['description' => 'Plan description.', 'example' => 'Dành cho giáo viên mới.'],
            'price' => ['description' => 'Price per cycle.', 'example' => 149000],
            'billing_cycle' => ['description' => 'month|year.', 'example' => 'month'],
            'features' => ['description' => 'Feature bullet list.', 'example' => ['Quản lý lớp học']],
            'limits' => ['description' => 'Quota caps keyed by resource (null = unlimited).', 'example' => ['students' => 50, 'parents' => 50]],
            'badge' => ['description' => 'Marketing badge.', 'example' => 'Phổ biến'],
            'is_active' => ['description' => 'Whether listed to tenants.', 'example' => true],
            'sort_order' => ['description' => 'Display order.', 'example' => 1],
        ];
    }
}
