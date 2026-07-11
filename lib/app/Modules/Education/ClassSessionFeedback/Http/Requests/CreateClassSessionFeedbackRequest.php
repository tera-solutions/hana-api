<?php

namespace App\Modules\Education\ClassSessionFeedback\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateClassSessionFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:edu_sessions,id'],
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'session_id' => [
                'description' => 'Class session id.',
                'example' => 1,
            ],
            'student_id' => [
                'description' => 'Student id.',
                'example' => 1,
            ],
            'rating' => [
                'description' => 'Optional 1-5 rating for the student in this session.',
                'example' => 4,
            ],
            'comment' => [
                'description' => 'Per-student note for this session.',
                'example' => 'Tham gia tích cực, cần luyện thêm phát âm.',
            ],
        ];
    }
}
