<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_plan_id' => ['required', 'integer', 'exists:edu_lesson_plans,id'],
            // Which specific template (bài học) of the plan to use — optional,
            // falls back to the plan's next unused template (by lesson_no).
            'lesson_plan_lesson_id' => ['nullable', 'integer', 'exists:edu_lesson_plan_lessons,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_plan_id.required' => 'Vui lòng chọn giáo án.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_plan_id' => ['description' => 'Giáo án mới cho buổi học (phải thuộc danh sách giáo án của lớp).', 'example' => 1],
            'lesson_plan_lesson_id' => [
                'description' => 'Bài học cụ thể trong giáo án để dùng (tùy chọn, mặc định lấy bài học kế tiếp chưa dùng).',
                'example' => 3,
            ],
        ];
    }
}
