<?php

namespace App\Modules\Education\Attendance\Http\Requests;

use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * session_id and student_id are immutable (the unique pair identifies the
 * row) and ignored if sent.
 */
class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', Rule::in(AttendanceStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
            'checkin_time' => ['nullable', 'date'],
            'checkout_time' => ['nullable', 'date'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'present|absent|late|excused.',
                'example' => 'absent',
            ],
            'note' => [
                'description' => 'Note.',
            ],
            'checkin_time' => [
                'description' => 'Check-in time.',
            ],
            'checkout_time' => [
                'description' => 'Check-out time.',
            ],
        ];
    }
}
