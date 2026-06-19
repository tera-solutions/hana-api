<?php

namespace App\Modules\Education\Exam\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamStatus;
use App\Modules\Education\Exam\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_name' => ['sometimes', 'string', 'max:255'],
            'exam_type' => ['sometimes', Rule::in(ExamType::values())],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'duration' => ['sometimes', 'integer', 'gt:0'],
            'total_score' => ['sometimes', 'numeric', 'gt:0'],
            'passing_score' => ['sometimes', 'numeric', 'gte:0'],
            'status' => ['sometimes', Rule::in(ExamStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_type.in' => 'Loại bài kiểm tra không hợp lệ.',
            'duration.gt' => 'Thời lượng phải lớn hơn 0.',
            'total_score.gt' => 'Tổng điểm phải lớn hơn 0.',
            'status.in' => 'Trạng thái không hợp lệ.',
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
            'passing_score' => ['description' => 'Minimum score to pass.', 'example' => 70],
            'status' => ['description' => 'Status: draft, published, archived.', 'example' => 'published'],
        ];
    }
}
