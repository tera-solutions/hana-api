<?php

namespace App\Modules\Education\Level\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_code' => ['required', 'string', 'max:255', 'unique:edu_levels,level_code'],
            'level_name' => ['required', 'string', 'max:255', 'unique:edu_levels,level_name'],
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'level_order' => ['required', 'integer', 'min:1'],
            'cefr_level' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'level_code.required' => 'Mã cấp độ là bắt buộc.',
            'level_code.unique' => 'Mã cấp độ đã tồn tại.',
            'level_name.required' => 'Tên cấp độ là bắt buộc.',
            'level_name.unique' => 'Tên cấp độ đã tồn tại.',
            'course_id.required' => 'Khóa học là bắt buộc.',
            'course_id.exists' => 'Khóa học không tồn tại.',
            'level_order.required' => 'Thứ tự cấp độ là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'level_code' => ['description' => 'Unique level code.', 'example' => 'KIDS-STARTER'],
            'level_name' => ['description' => 'Level name.', 'example' => 'Starter'],
            'course_id' => ['description' => 'Owning course id.', 'example' => 1],
            'level_order' => ['description' => 'Position in the learning path (1-based).', 'example' => 1],
            'cefr_level' => ['description' => 'CEFR mapping.', 'example' => 'Pre-A1'],
            'description' => ['description' => 'Description.'],
            'status' => ['description' => 'Status: active or inactive (default active).', 'example' => 'active'],
        ];
    }
}
