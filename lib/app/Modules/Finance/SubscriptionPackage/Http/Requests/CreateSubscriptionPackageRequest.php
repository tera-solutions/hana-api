<?php

namespace App\Modules\Finance\SubscriptionPackage\Http\Requests;

use App\Modules\Finance\SubscriptionPackage\Models\SubscriptionPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSubscriptionPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fin_subscription_packages,name'],
            'type' => ['required', Rule::in([
                SubscriptionPackage::TYPE_SESSION,
                SubscriptionPackage::TYPE_MONTH,
                SubscriptionPackage::TYPE_TERM,
                SubscriptionPackage::TYPE_CUSTOM,
            ])],
            'price' => [
                Rule::requiredIf(fn () => $this->input('type') !== SubscriptionPackage::TYPE_CUSTOM),
                'nullable', 'numeric', 'gt:0',
            ],
            'sessions_included' => ['nullable', 'integer', 'min:1'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'applicable_courses' => ['nullable', 'array'],
            'applicable_courses.*' => ['integer', 'exists:edu_courses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên gói.',
            'name.unique' => 'Tên gói đã tồn tại.',
            'type.required' => 'Vui lòng chọn loại gói.',
            'price.required' => 'Giá không hợp lệ.',
            'price.gt' => 'Giá không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên gói.', 'example' => 'Gói tháng'],
            'type' => ['description' => 'Loại gói: session|month|term|custom.', 'example' => 'month'],
            'price' => ['description' => 'Giá (bắt buộc trừ loại custom).', 'example' => 2400000],
            'sessions_included' => ['description' => 'Số buổi bao gồm.', 'example' => 12],
            'duration_days' => ['description' => 'Thời hạn (ngày).', 'example' => 30],
            'applicable_courses' => ['description' => 'Danh sách course_id áp dụng, bỏ trống = tất cả.', 'example' => [1, 2]],
        ];
    }
}
