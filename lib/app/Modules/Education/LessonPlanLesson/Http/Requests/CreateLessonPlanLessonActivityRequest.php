<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLessonPlanLessonActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_plan_lesson_id' => ['required', 'integer', 'exists:edu_lesson_plan_lessons,id'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(LessonActivityStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_plan_lesson_id.required' => 'Vui lòng chọn buổi học.',
            'title.required' => 'Vui lòng nhập tiêu đề hoạt động.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_plan_lesson_id' => ['description' => 'ID buổi học trong giáo án.', 'example' => 1],
            'sort_order' => ['description' => 'Thứ tự hiển thị (bỏ trống để thêm vào cuối).', 'example' => 1],
            'avatar' => ['description' => 'Avatar URL (optional).'],
            'title' => ['description' => 'Tiêu đề hoạt động.', 'example' => 'Warm-up'],
            'description' => ['description' => 'Mô tả hoạt động (optional).', 'example' => 'Khởi động, gợi mở chủ đề bài học.'],
            'duration' => ['description' => 'Thời lượng (phút).', 'example' => 10],
            'status' => ['description' => 'pending | in_progress | completed.', 'example' => 'pending'],
        ];
    }
}
