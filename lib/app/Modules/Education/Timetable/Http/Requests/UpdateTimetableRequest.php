<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use App\Modules\Education\Timetable\Enums\TimetableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Updates timetable metadata. Regenerating sessions from changed rules is a separate
 * operation (reschedule), so the recurrence config is not edited here.
 */
class UpdateTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],
            'status' => ['sometimes', Rule::in(TimetableStatus::values())],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên thời khóa biểu.', 'example' => 'TKB Starter A (cập nhật)'],
            'status' => ['description' => 'Trạng thái: draft|active|completed|cancelled.', 'example' => 'active'],
            'teacher_id' => ['description' => 'Giáo viên phụ trách.', 'example' => 2],
            'room_id' => ['description' => 'Phòng học.', 'example' => 2],
        ];
    }
}
