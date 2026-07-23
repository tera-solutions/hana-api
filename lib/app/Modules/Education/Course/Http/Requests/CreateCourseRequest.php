<?php

namespace App\Modules\Education\Course\Http\Requests;

use App\Modules\Education\Course\Enums\CourseTuitionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => ['nullable', 'string', 'max:255'],
            // Omit to let the backend auto-generate a code (CourseService::create()).
            'code' => ['nullable', 'string', 'max:255', 'regex:/^[A-Z0-9_]+$/', 'unique:edu_courses,code'],
            'thumbnail' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'price_per_lesson' => ['required', 'numeric', 'min:0'],
            'tuition_type' => ['nullable', 'string', Rule::in(CourseTuitionType::values())],
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
            'title' => [
                'description' => 'Optional display title, separate from the internal course name.',
                'example' => 'IELTS Foundation — Lộ trình 6.5',
            ],
            'code' => [
                'description' => 'Unique code (A-Z, 0-9, _). Omit to auto-generate (e.g. CRS000001).',
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
            'tuition_type' => [
                'description' => 'How tuition is framed: per_lesson|per_course|per_month (defaults to per_lesson). Display-only, does not change how amounts are computed.',
                'example' => 'per_lesson',
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
