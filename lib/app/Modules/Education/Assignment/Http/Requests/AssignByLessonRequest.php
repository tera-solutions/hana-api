<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignByLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'integer', 'exists:edu_lessons,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_id.required' => 'Bài học là bắt buộc.',
            'lesson_id.exists' => 'Bài học không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_id' => ['description' => 'Assign to every active student of this lesson\'s class.', 'example' => 1],
        ];
    }
}
