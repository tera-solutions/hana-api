<?php

namespace App\Modules\Education\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9_]+$/', 'unique:edu_courses,code'],
            'thumbnail' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'price_per_lesson' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],
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
                'example' => 'https://cdn.hana.edu.vn/c.png',
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
            'is_active' => [
                'description' => 'Active state.',
                'example' => true,
            ],
            'business_id' => [
                'description' => 'Owning business id.',
                'example' => 1,
            ],
        ];
    }
}
