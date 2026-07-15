<?php

namespace App\Modules\Education\CourseCurriculum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'order' => ['sometimes', 'required', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Tiêu đề nội dung chương trình học.', 'example' => 'Nghe hiểu — Listening comprehension'],
            'order' => ['description' => 'Thứ tự hiển thị.', 'example' => 1],
            'content' => ['description' => 'Nội dung chi tiết (optional).', 'example' => 'Luyện nghe các đoạn hội thoại ngắn.'],
        ];
    }
}
