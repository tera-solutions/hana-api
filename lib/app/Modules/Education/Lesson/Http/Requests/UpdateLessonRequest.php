<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use App\Modules\Education\Lesson\Enums\LessonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * lesson_no and class_room_id are immutable (lesson.md §8). Date/time/room
 * changes go through reschedule; cancel/lock/unlock go through their own
 * endpoints, so status here is limited to the plain progression states.
 */
class UpdateLessonRequest extends FormRequest
{
    private const ALLOWED_STATUSES = [
        LessonStatus::Scheduled->value,
        LessonStatus::Confirmed->value,
        LessonStatus::InProgress->value,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:hr_teachers,id'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'lesson_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(self::ALLOWED_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái không hợp lệ. Dùng cancel/lock/unlock để đổi các trạng thái đó.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teacher_id' => ['description' => 'Teacher id.', 'example' => 2],
            'avatar' => [
                'description' => 'Avatar URL.',
            ],
            'lesson_note' => ['description' => 'Lesson note.', 'example' => 'Học viên tiếp thu tốt.'],
            'status' => [
                'description' => 'Lesson status. Only scheduled, confirmed, in_progress accepted here — use cancel/lock/unlock for other transitions.',
                'example' => 'confirmed',
            ],
        ];
    }
}
