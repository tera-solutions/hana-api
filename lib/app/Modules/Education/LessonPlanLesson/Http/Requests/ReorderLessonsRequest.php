<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLessonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_ids' => ['required', 'array', 'min:1'],
            'lesson_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_ids.required' => 'Danh sách buổi học là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_ids' => ['description' => 'Lesson ids in the desired order; reassigned to 1..N.', 'example' => [3, 1, 2]],
        ];
    }
}
