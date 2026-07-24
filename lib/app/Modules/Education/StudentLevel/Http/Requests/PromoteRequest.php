<?php

namespace App\Modules\Education\StudentLevel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'reason_type' => ['nullable', Rule::in(['exam', 'evaluation', 'other'])],
            'exam_result_id' => [Rule::requiredIf(fn () => $this->input('reason_type') === 'exam'), 'nullable', 'integer', 'exists:edu_exam_results,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_type.in' => 'Căn cứ thay đổi không hợp lệ.',
            'exam_result_id.required' => 'Vui lòng chọn bài kiểm tra liên quan.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'target_level_id' => ['description' => 'Explicit target level; defaults to the next level in the path.', 'example' => 2],
            'reason' => ['description' => 'Promotion note.', 'example' => 'Đạt yêu cầu lên cấp.'],
            'reason_type' => ['description' => 'Căn cứ: exam | evaluation | other.', 'example' => 'exam'],
            'exam_result_id' => ['description' => 'Bắt buộc khi reason_type = exam.', 'example' => 12],
        ];
    }
}
