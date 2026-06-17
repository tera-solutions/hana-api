<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * lesson_no is changed via reorder, not here.
 */
class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_title' => ['sometimes', 'required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'vocabulary' => ['nullable', 'string', 'max:5000'],
            'grammar' => ['nullable', 'string', 'max:5000'],
            'activities' => ['nullable', 'string', 'max:5000'],
            'homework' => ['nullable', 'string', 'max:5000'],
            'duration' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_title.required' => 'Tiêu đề buổi học là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_title' => ['description' => 'Lesson title.', 'example' => 'My Family'],
            'objective' => ['description' => 'Learning objective.'],
            'vocabulary' => ['description' => 'Vocabulary.'],
            'grammar' => ['description' => 'Grammar point.'],
            'activities' => ['description' => 'Activities.'],
            'homework' => ['description' => 'Homework.'],
            'duration' => ['description' => 'Duration in minutes.', 'example' => 60],
        ];
    }
}
