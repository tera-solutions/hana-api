<?php

namespace App\Modules\Education\StudentLevel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'reason_type' => ['nullable', Rule::in(['exam', 'evaluation', 'other'])],
            'exam_result_id' => [Rule::requiredIf(fn () => $this->input('reason_type') === 'exam'), 'nullable', 'integer', 'exists:edu_exam_results,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_level_id.required' => 'Cấp độ mục tiêu là bắt buộc.',
            'reason.required' => 'Lý do điều chỉnh là bắt buộc.',
            'reason_type.in' => 'Căn cứ thay đổi không hợp lệ.',
            'exam_result_id.required' => 'Vui lòng chọn bài kiểm tra liên quan.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'target_level_id' => ['description' => 'Target level id (must belong to the same course).', 'example' => 1],
            'reason' => ['description' => 'Reason for the manual adjustment.', 'example' => 'Đánh giá đầu vào chưa chính xác.'],
            'reason_type' => ['description' => 'Căn cứ: exam | evaluation | other.', 'example' => 'evaluation'],
            'exam_result_id' => ['description' => 'Bắt buộc khi reason_type = exam.', 'example' => 12],
        ];
    }
}
