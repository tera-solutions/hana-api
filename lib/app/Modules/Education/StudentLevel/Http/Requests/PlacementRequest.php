<?php

namespace App\Modules\Education\StudentLevel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['required', 'integer', 'exists:edu_levels,id'],
            'assessment_type' => ['nullable', Rule::in(['placement_test', 'teacher_evaluation'])],
            'score' => ['nullable', 'numeric', 'between:0,100'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Học viên là bắt buộc.',
            'course_id.required' => 'Khóa học là bắt buộc.',
            'level_id.required' => 'Cấp độ là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_id' => ['description' => 'Student id.', 'example' => 1],
            'course_id' => ['description' => 'Course the level belongs to.', 'example' => 1],
            'level_id' => ['description' => 'Assigned level id (must belong to the course).', 'example' => 1],
            'assessment_type' => ['description' => 'Assessment source: placement_test or teacher_evaluation.', 'example' => 'placement_test'],
            'score' => ['description' => 'Placement score (0–100).', 'example' => 45],
            'comment' => ['description' => 'Assessor note.', 'example' => 'Good speaking, weak grammar.'],
        ];
    }
}
