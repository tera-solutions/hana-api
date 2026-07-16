<?php

namespace App\Modules\System\Superadmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months' => ['required', 'integer', 'min:1', 'max:36'],
        ];
    }

    public function messages(): array
    {
        return [
            'months.required' => 'Vui lòng nhập số tháng gia hạn.',
            'months.integer' => 'Số tháng không hợp lệ.',
            'months.min' => 'Số tháng gia hạn tối thiểu là 1.',
            'months.max' => 'Số tháng gia hạn tối đa là 36.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'months' => ['description' => 'Number of months to extend the active subscription by.', 'example' => 3],
        ];
    }
}
