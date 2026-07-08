<?php

namespace App\Modules\HR\Achievement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeacherReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:hr_teachers,id'],
            'student_id' => ['nullable', 'integer', 'exists:edu_students,id'],
            'class_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Vui lòng chọn giáo viên.',
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teacher_id' => ['description' => 'The teacher being reviewed.', 'example' => 1],
            'student_id' => ['description' => 'The student the review is about.', 'example' => 1],
            'class_id' => ['description' => 'The class the review relates to.', 'example' => 1],
            'rating' => ['description' => 'Star rating, 1-5.', 'example' => 5],
            'content' => ['description' => 'Review text.', 'example' => 'Cô dạy rất nhiệt tình.'],
        ];
    }
}
