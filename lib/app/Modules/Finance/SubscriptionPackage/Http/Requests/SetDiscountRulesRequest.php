<?php

namespace App\Modules\Finance\SubscriptionPackage\Http\Requests;

use App\Modules\Finance\SubscriptionPackage\Models\SubscriptionPackageDiscountRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDiscountRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.type' => ['required', Rule::in([
                SubscriptionPackageDiscountRule::TYPE_MULTI_TERM,
                SubscriptionPackageDiscountRule::TYPE_SIBLING,
                SubscriptionPackageDiscountRule::TYPE_CODE,
            ])],
            'rules.*.value' => ['required', 'numeric', 'min:0', 'max:100'],
            'rules.*.condition' => ['nullable', 'string', 'max:255'],
            'rules.*.enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rules.*.value.max' => 'Phần trăm giảm giá không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'rules' => ['description' => 'Danh sách quy tắc giảm giá (thay thế toàn bộ).', 'example' => [
                ['type' => 'multi_term', 'value' => 10, 'condition' => '3 tháng', 'enabled' => true],
            ]],
        ];
    }
}
