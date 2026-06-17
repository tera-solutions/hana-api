<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_no' => ['nullable', 'integer', 'min:1'],
            'lesson_title' => ['required', 'string', 'max:255'],
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
            'lesson_no' => ['description' => 'Lesson order (must be the next number to stay contiguous; omit to auto-assign).', 'example' => 1],
            'lesson_title' => ['description' => 'Lesson title.', 'example' => 'My Family'],
            'objective' => ['description' => 'Learning objective.', 'example' => 'Giới thiệu thành viên gia đình'],
            'vocabulary' => ['description' => 'Vocabulary.', 'example' => 'Father, Mother, Brother, Sister'],
            'grammar' => ['description' => 'Grammar point.', 'example' => 'This is my...'],
            'activities' => ['description' => 'Activities.', 'example' => 'Flashcard, Speaking'],
            'homework' => ['description' => 'Homework.', 'example' => 'Workbook page 10'],
            'duration' => ['description' => 'Duration in minutes.', 'example' => 60],
        ];
    }
}
