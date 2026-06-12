<?php

namespace App\Modules\Education\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required Course name. Example: IELTS Foundation
 * @bodyParam code string required Unique code (A-Z, 0-9, _). Example: IELTS_6_5
 * @bodyParam thumbnail string Thumbnail URL. Example: https://cdn.hana.edu.vn/c.png
 * @bodyParam duration_minutes integer required Lesson duration in minutes (> 0). Example: 90
 * @bodyParam price_per_lesson number required Price per lesson (>= 0). Example: 250000
 * @bodyParam description string Description.
 * @bodyParam is_active boolean Active state. Example: true
 * @bodyParam business_id integer Owning business id. Example: 1
 */
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
}
