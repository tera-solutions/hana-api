<?php

namespace App\Modules\Education\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'session_id' => ['nullable', 'integer', 'exists:edu_sessions,id'],
            'student_id' => ['nullable', 'integer', 'exists:edu_students,id'],
            'status' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_id' => [
                'description' => 'Filter by the session\'s class id.',
                'example' => 1,
            ],
            'session_id' => [
                'description' => 'Filter by session id.',
                'example' => 1,
            ],
            'date_from' => [
                'description' => 'Session date on/after (Y-m-d).',
                'example' => '2026-06-01',
            ],
            'date_to' => [
                'description' => 'Session date on/before (Y-m-d).',
                'example' => '2026-06-30',
            ],
        ];
    }
}
