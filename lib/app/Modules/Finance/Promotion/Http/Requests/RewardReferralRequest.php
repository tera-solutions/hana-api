<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reward_amount' => ['description' => 'Giá trị thưởng thực trả (ghi đè giá trị dự kiến nếu có).', 'example' => 100000],
        ];
    }
}
