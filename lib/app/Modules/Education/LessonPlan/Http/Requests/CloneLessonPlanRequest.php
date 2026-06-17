<?php

namespace App\Modules\Education\LessonPlan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'string', 'max:255', 'unique:edu_lesson_plans,plan_code'],
            'plan_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_code.required' => 'Mã giáo án mới là bắt buộc.',
            'plan_code.unique' => 'Mã giáo án đã tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'plan_code' => ['description' => 'Code for the cloned plan.', 'example' => 'KIDS_STARTER_V2'],
            'plan_name' => ['description' => 'Name for the cloned plan (defaults to source).', 'example' => 'Kids Starter'],
        ];
    }
}
