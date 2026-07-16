<?php

namespace App\Modules\System\Superadmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_\-]+$/', 'unique:sys_packages,package_code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', Rule::in(['month', 'year'])],
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
            'package_code.required' => 'Vui lòng nhập mã gói.',
            'package_code.regex' => 'Mã gói chỉ gồm chữ in hoa, số, gạch dưới và gạch ngang.',
            'package_code.unique' => 'Mã gói đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên gói.',
            'price.required' => 'Vui lòng nhập giá.',
            'price.numeric' => 'Giá không hợp lệ.',
            'billing_cycle.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'limits.*.integer' => 'Giới hạn phải là số nguyên.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'package_code' => ['description' => 'Unique code (upper/number/_/-).', 'example' => 'PKG-BASIC'],
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
