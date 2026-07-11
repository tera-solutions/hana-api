<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonPlanLessonActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['sometimes', 'required', 'integer', 'min:1'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(LessonActivityStatus::values())],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'sort_order' => ['description' => 'Thứ tự hiển thị.', 'example' => 1],
            'avatar' => ['description' => 'Avatar URL (optional).'],
            'title' => ['description' => 'Tiêu đề hoạt động.', 'example' => 'Warm-up'],
            'description' => ['description' => 'Mô tả hoạt động (optional).', 'example' => 'Khởi động, gợi mở chủ đề bài học.'],
            'duration' => ['description' => 'Thời lượng (phút).', 'example' => 10],
            'status' => ['description' => 'pending | in_progress | completed.', 'example' => 'in_progress'],
        ];
    }
}
