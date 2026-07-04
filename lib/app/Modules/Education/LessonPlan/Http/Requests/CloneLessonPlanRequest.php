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
            'plan_code' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:edu_lesson_plans,plan_code'],
            'plan_name' => ['nullable', 'string', 'min:2', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_code.required' => 'Mã giáo án mới là bắt buộc.',
            'plan_code.min' => 'Mã giáo án phải có ít nhất 2 ký tự.',
            'plan_code.max' => 'Mã giáo án không được vượt quá 50 ký tự.',
            'plan_code.regex' => 'Mã giáo án chỉ được chứa chữ, số và dấu gạch dưới.',
            'plan_code.unique' => 'Mã giáo án đã tồn tại.',
            'plan_name.min' => 'Tên giáo án phải có ít nhất 2 ký tự.',
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
