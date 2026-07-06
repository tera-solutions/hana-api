<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Requests;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'homework' => ['nullable', 'string', 'max:5000'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'activities' => ['nullable', 'array'],
            'activities.*.avatar' => ['nullable', 'string', 'max:1000'],
            'activities.*.title' => ['required', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string', 'max:5000'],
            'activities.*.duration' => ['nullable', 'integer', 'min:1'],
            'activities.*.status' => ['nullable', Rule::enum(LessonActivityStatus::class)],
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
            'objective' => ['description' => 'Learning objectives, multiple values joined by ";".', 'example' => 'Giới thiệu thành viên gia đình;Ghi nhớ từ vựng gia đình'],
            'vocabulary' => ['description' => 'Vocabulary.', 'example' => 'Father, Mother, Brother, Sister'],
            'grammar' => ['description' => 'Grammar point.', 'example' => 'This is my...'],
            'homework' => ['description' => 'Homework.', 'example' => 'Workbook page 10'],
            'duration' => ['description' => 'Duration in minutes.', 'example' => 60],
            'activities' => ['description' => 'Ordered list of activities (replaces the whole set on update).'],
            'activities.*.avatar' => ['description' => 'Activity avatar/icon URL.'],
            'activities.*.title' => ['description' => 'Activity title.', 'example' => 'Warm-up'],
            'activities.*.description' => ['description' => 'Activity description.'],
            'activities.*.duration' => ['description' => 'Activity duration in minutes.', 'example' => 5],
            'activities.*.status' => ['description' => 'Activity status.', 'example' => 'pending'],
        ];
    }
}
