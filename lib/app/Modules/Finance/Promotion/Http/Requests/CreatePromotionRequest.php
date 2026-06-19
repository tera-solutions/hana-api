<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use App\Modules\Finance\Promotion\Enums\DiscountType;
use App\Modules\Finance\Promotion\Enums\PromotionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'promotion_name' => ['required', 'string', 'max:255'],
            'promotion_type' => ['required', Rule::in(PromotionType::values())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'priority' => ['nullable', 'integer', 'min:0'],

            'discount_type' => ['nullable', Rule::in(DiscountType::values())],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'bonus_lesson' => ['nullable', 'integer', 'min:0'],
            'bonus_wallet_amount' => ['nullable', 'numeric', 'min:0'],

            'rules' => ['nullable', 'array'],
            'rules.*.rule_type' => ['required_with:rules', 'string'],
            'rules.*.rule_value' => ['nullable', 'string'],

            'rewards' => ['nullable', 'array'],
            'rewards.*.reward_type' => ['required_with:rewards', 'string'],
            'rewards.*.reward_value' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'promotion_name.required' => 'Tên chương trình là bắt buộc.',
            'promotion_type.required' => 'Loại khuyến mãi là bắt buộc.',
            'promotion_type.in' => 'Loại khuyến mãi không hợp lệ.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'promotion_name' => ['description' => 'Tên chương trình.', 'example' => 'Hè 2026'],
            'promotion_type' => ['description' => 'Loại: discount | gift_lesson | wallet_credit | voucher | referral | combo.', 'example' => 'discount'],
            'start_date' => ['description' => 'Ngày bắt đầu (Y-m-d).', 'example' => '2026-06-01'],
            'end_date' => ['description' => 'Ngày kết thúc (Y-m-d).', 'example' => '2026-08-31'],
            'priority' => ['description' => 'Độ ưu tiên khi chọn khuyến mãi tốt nhất.', 'example' => 10],
            'discount_type' => ['description' => 'percent | fixed.', 'example' => 'percent'],
            'discount_value' => ['description' => 'Giá trị giảm (theo % hoặc số tiền).', 'example' => 10],
            'max_discount' => ['description' => 'Mức giảm tối đa (với loại percent).', 'example' => 500000],
            'bonus_lesson' => ['description' => 'Số buổi học tặng.', 'example' => 2],
            'bonus_wallet_amount' => ['description' => 'Số credit ví tặng.', 'example' => 200000],
            'rules' => ['description' => 'Điều kiện áp dụng [{rule_type, rule_value}].', 'example' => [['rule_type' => 'min_order', 'rule_value' => '5000000']]],
            'rewards' => ['description' => 'Phần thưởng [{reward_type, reward_value}].', 'example' => [['reward_type' => 'discount', 'reward_value' => '10']]],
        ];
    }
}
