<?php

namespace App\Modules\Education\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Course code becomes immutable once classes exist (ignored in that case).
 * is_active is changed via suspend/restore, not here.
 */
class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[A-Z0-9_]+$/', Rule::unique('edu_courses', 'code')->ignore($id)],
            'thumbnail' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1'],
            'price_per_lesson' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã khóa học đã tồn tại.',
            'code.regex' => 'Mã khóa học chỉ gồm chữ in hoa, số và dấu gạch dưới.',
            'duration_minutes.min' => 'Thời lượng phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Course name.',
                'example' => 'IELTS Foundation',
            ],
            'code' => [
                'description' => 'Unique code (A-Z, 0-9, _).',
                'example' => 'IELTS_6_5',
            ],
            'thumbnail' => [
                'description' => 'Thumbnail URL.',
            ],
            'duration_minutes' => [
                'description' => 'Lesson duration in minutes (> 0).',
                'example' => 90,
            ],
            'price_per_lesson' => [
                'description' => 'Price per lesson (>= 0).',
                'example' => 250000,
            ],
            'description' => [
                'description' => 'Description.',
            ],
        ];
    }
}
