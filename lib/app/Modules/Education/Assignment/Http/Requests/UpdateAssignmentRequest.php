<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use App\Modules\Education\Assignment\Enums\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * assignment_code and status are immutable here (status changes via publish).
 */
class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_name' => ['sometimes', 'required', 'string', 'max:255'],
            'assignment_type' => ['sometimes', 'required', Rule::in(AssignmentType::values())],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer'],
            'lesson_id' => ['nullable', 'integer', 'exists:edu_lessons,id'],
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instruction' => ['sometimes', 'required', 'string', 'max:10000'],
            'max_score' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'due_date' => ['sometimes', 'required', 'date'],
            'allow_late_submission' => ['nullable', 'boolean'],
            'allow_multiple_submission' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_name.required' => 'Tên bài tập là bắt buộc.',
            'assignment_type.in' => 'Loại bài tập không hợp lệ.',
            'instruction.required' => 'Hướng dẫn là bắt buộc.',
            'max_score.gt' => 'Điểm tối đa phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'assignment_name' => ['description' => 'Assignment name.', 'example' => 'Unit 1 Homework'],
            'assignment_type' => ['description' => 'Assignment type.', 'example' => 'homework'],
            'instruction' => ['description' => 'Instruction for students.', 'example' => 'Hoàn thành bài tập trang 10.'],
            'max_score' => ['description' => 'Maximum score (> 0).', 'example' => 10],
            'due_date' => ['description' => 'Due date (Y-m-d H:i:s).', 'example' => '2026-07-10 23:59:00'],
            'allow_late_submission' => ['description' => 'Allow submitting after the due date.', 'example' => false],
            'allow_multiple_submission' => ['description' => 'Allow re-submission.', 'example' => false],
        ];
    }
}
