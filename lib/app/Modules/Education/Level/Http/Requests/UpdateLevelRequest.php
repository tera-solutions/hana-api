<?php

namespace App\Modules\Education\Level\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'level_code' => ['sometimes', 'string', 'max:255', Rule::unique('edu_levels', 'level_code')->ignore($id)],
            'level_name' => ['sometimes', 'string', 'max:255', Rule::unique('edu_levels', 'level_name')->ignore($id)],
            'course_id' => ['sometimes', 'integer', 'exists:edu_courses,id'],
            'level_order' => ['sometimes', 'integer', 'min:1'],
            'cefr_level' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'level_code.unique' => 'Mã cấp độ đã tồn tại.',
            'level_name.unique' => 'Tên cấp độ đã tồn tại.',
            'course_id.exists' => 'Khóa học không tồn tại.',
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
            'status' => ['description' => 'Status: active or inactive.', 'example' => 'active'],
        ];
    }
}
