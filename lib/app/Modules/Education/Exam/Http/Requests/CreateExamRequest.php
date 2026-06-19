<?php

namespace App\Modules\Education\Exam\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create input per spec §VI. exam_code, version and status are managed by the service.
 */
class CreateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_name' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', Rule::in(ExamType::values())],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'duration' => ['required', 'integer', 'gt:0'],
            'total_score' => ['required', 'numeric', 'gt:0'],
            'passing_score' => ['required', 'numeric', 'gte:0', 'lte:total_score'],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_name.required' => 'Tên bài kiểm tra là bắt buộc.',
            'exam_type.required' => 'Loại bài kiểm tra là bắt buộc.',
            'exam_type.in' => 'Loại bài kiểm tra không hợp lệ.',
            'duration.gt' => 'Thời lượng phải lớn hơn 0.',
            'total_score.gt' => 'Tổng điểm phải lớn hơn 0.',
            'passing_score.lte' => 'Điểm đạt không được vượt quá tổng điểm.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'exam_name' => ['description' => 'Exam name.', 'example' => 'Final Test - Starter'],
            'exam_type' => ['description' => 'Type: placement, progress, midterm, final, promotion, mock_test, certification.', 'example' => 'final'],
            'course_id' => ['description' => 'Owning course (optional).', 'example' => 1],
            'level_id' => ['description' => 'Owning level (optional).', 'example' => 1],
            'duration' => ['description' => 'Duration in minutes (> 0).', 'example' => 60],
            'total_score' => ['description' => 'Maximum total score (> 0).', 'example' => 100],
            'passing_score' => ['description' => 'Minimum score to pass (<= total_score).', 'example' => 70],
        ];
    }
}
