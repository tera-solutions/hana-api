<?php

namespace App\Modules\Education\LessonPlan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Status changes go through publish/clone/archive; course_id is immutable.
 */
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
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_code.unique' => 'Mã giáo án đã tồn tại.',
            'plan_name.required' => 'Tên giáo án là bắt buộc.',
            'level_id.exists' => 'Cấp độ không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'plan_code' => ['description' => 'Unique lesson-plan code.', 'example' => 'KIDS_STARTER_V1'],
            'plan_name' => ['description' => 'Lesson-plan name.', 'example' => 'Kids Starter'],
            'level_id' => ['description' => 'Level id.', 'example' => 1],
            'description' => ['description' => 'Description.'],
        ];
    }
}
