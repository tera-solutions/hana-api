<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * lesson_no and class_room_id are immutable (lesson.md §8). Date/time/room
 * changes go through reschedule.
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
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:hr_teachers,id'],
            'lesson_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teacher_id' => ['description' => 'Teacher id.', 'example' => 2],
            'lesson_note' => ['description' => 'Lesson note.', 'example' => 'Học viên tiếp thu tốt.'],
        ];
    }
}
