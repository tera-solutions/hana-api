<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referrer_parent_id' => ['required', 'integer', 'exists:crm_parents,id', 'different:referred_parent_id'],
            'referred_parent_id' => ['required', 'integer', 'exists:crm_parents,id'],
            'promotion_id' => ['nullable', 'integer', 'exists:fin_promotions,id'],
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'referrer_parent_id.required' => 'Phụ huynh giới thiệu là bắt buộc.',
            'referrer_parent_id.different' => 'Phụ huynh giới thiệu và được giới thiệu phải khác nhau.',
            'referred_parent_id.required' => 'Phụ huynh được giới thiệu là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'referrer_parent_id' => ['description' => 'Phụ huynh giới thiệu.', 'example' => 1],
            'referred_parent_id' => ['description' => 'Phụ huynh được giới thiệu.', 'example' => 2],
            'promotion_id' => ['description' => 'Chương trình giới thiệu liên quan.', 'example' => 1],
            'reward_amount' => ['description' => 'Giá trị thưởng dự kiến.', 'example' => 100000],
        ];
    }
}
