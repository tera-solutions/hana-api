<?php

namespace App\Modules\Education\StudentLevel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level_id' => ['required', 'integer', 'exists:edu_levels,id'],
            'reason' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_level_id.required' => 'Cấp độ mục tiêu là bắt buộc.',
            'reason.required' => 'Lý do điều chỉnh là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'target_level_id' => ['description' => 'Target level id (must belong to the same course).', 'example' => 1],
            'reason' => ['description' => 'Reason for the manual adjustment.', 'example' => 'Đánh giá đầu vào chưa chính xác.'],
        ];
    }
}
