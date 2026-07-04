<?php

namespace App\Modules\Education\Attendance\Http\Requests;

use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAttendanceRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(AttendanceStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
            'checkin_time' => ['nullable', 'date'],
            'checkout_time' => ['nullable', 'date'],
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
            'status' => [
                'description' => 'present|absent|late|excused.',
                'example' => 'present',
            ],
            'note' => [
                'description' => 'Note.',
            ],
            'checkin_time' => [
                'description' => 'Check-in time.',
                'example' => '2026-06-25 08:00:00',
            ],
            'checkout_time' => [
                'description' => 'Check-out time.',
            ],
        ];
    }
}
