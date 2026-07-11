<?php

namespace App\Modules\Education\CourseCurriculum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Vui lòng chọn khóa học.',
            'title.required' => 'Vui lòng nhập tiêu đề chương trình học.',
            'order.required' => 'Vui lòng nhập thứ tự.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'course_id' => ['description' => 'ID khóa học.', 'example' => 1],
            'title' => ['description' => 'Tiêu đề nội dung chương trình học.', 'example' => 'Nghe hiểu — Listening comprehension'],
            'order' => ['description' => 'Thứ tự hiển thị.', 'example' => 1],
            'content' => ['description' => 'Nội dung chi tiết (optional).', 'example' => 'Luyện nghe các đoạn hội thoại ngắn.'],
        ];
    }
}
