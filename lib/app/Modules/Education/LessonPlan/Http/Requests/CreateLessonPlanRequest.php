<?php

namespace App\Modules\Education\LessonPlan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:edu_lesson_plans,plan_code'],
            'plan_name' => ['required', 'string', 'min:2', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_code.required' => 'Mã giáo án là bắt buộc.',
            'plan_code.min' => 'Mã giáo án phải có ít nhất 2 ký tự.',
            'plan_code.max' => 'Mã giáo án không được vượt quá 50 ký tự.',
            'plan_code.regex' => 'Mã giáo án chỉ được chứa chữ, số và dấu gạch dưới.',
            'plan_code.unique' => 'Mã giáo án đã tồn tại.',
            'plan_name.required' => 'Tên giáo án là bắt buộc.',
            'plan_name.min' => 'Tên giáo án phải có ít nhất 2 ký tự.',
            'course_id.required' => 'Khóa học là bắt buộc.',
            'course_id.exists' => 'Khóa học không tồn tại.',
            'level_id.exists' => 'Cấp độ không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'plan_code' => ['description' => 'Unique lesson-plan code.', 'example' => 'KIDS_STARTER_V1'],
            'plan_name' => ['description' => 'Lesson-plan name.', 'example' => 'Kids Starter'],
            'avatar' => [
                'description' => 'Avatar URL.',
                'example' => 'https://cdn.hana.edu.vn/a.png',
            ],
            'course_id' => ['description' => 'Owning course id.', 'example' => 1],
            'level_id' => ['description' => 'Level id.', 'example' => 1],
            'description' => ['description' => 'Description.'],
        ];
    }
}
