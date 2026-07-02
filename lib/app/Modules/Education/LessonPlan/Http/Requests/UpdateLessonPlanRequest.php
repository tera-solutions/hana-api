<?php

namespace App\Modules\Education\LessonPlan\Http\Requests;

use App\Modules\Education\LessonPlan\Enums\LessonPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'plan_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('edu_lesson_plans', 'plan_code')->ignore($id)],
            'plan_name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'course_id' => ['sometimes', 'required', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'status' => ['sometimes', 'required', Rule::enum(LessonPlanStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_code.unique' => 'Mã giáo án đã tồn tại.',
            'plan_name.required' => 'Tên giáo án là bắt buộc.',
            'course_id.required' => 'Khóa học là bắt buộc.',
            'course_id.exists' => 'Khóa học không tồn tại.',
            'level_id.exists' => 'Cấp độ không tồn tại.',
            'status.enum' => 'Trạng thái không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'plan_code' => ['description' => 'Unique lesson-plan code.', 'example' => 'KIDS_STARTER_V1'],
            'plan_name' => ['description' => 'Lesson-plan name.', 'example' => 'Kids Starter'],
            'avatar' => [
                'description' => 'Avatar URL.',
            ],
            'course_id' => ['description' => 'Owning course id.', 'example' => 1],
            'level_id' => ['description' => 'Level id.', 'example' => 1],
            'status' => ['description' => 'Lesson-plan status.', 'example' => 'published'],
            'description' => ['description' => 'Description.'],
        ];
    }
}
