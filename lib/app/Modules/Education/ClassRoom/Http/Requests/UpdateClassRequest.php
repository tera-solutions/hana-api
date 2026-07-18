<?php

namespace App\Modules\Education\ClassRoom\Http\Requests;

use App\Modules\Education\ClassRoom\Enums\ClassLearningType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'lesson_plan_id' => ['nullable', 'integer', 'exists:edu_lesson_plans,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'room_id' => ['nullable', 'integer'],
            'learning_type' => ['sometimes', Rule::in(ClassLearningType::values())],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'min_warning_capacity' => ['nullable', 'integer', 'min:0'],
            'min_capacity' => ['nullable', 'integer', 'min:0'],
            'max_warning_capacity' => ['nullable', 'integer', 'min:0'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên lớp học.', 'example' => 'IELTS Foundation - Khai giảng tháng 7'],
            'avatar' => [
                'description' => 'Avatar URL.',
            ],
            'teacher_id' => ['description' => 'ID giáo viên phụ trách.', 'example' => 2],
            'assignee_id' => ['description' => 'ID nhân viên phụ trách.', 'example' => 5],
        ];
    }
}
