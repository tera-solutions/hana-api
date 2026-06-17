<?php

namespace App\Modules\Education\Lesson\Http\Requests;

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
            'class_room_id' => ['required', 'integer', 'exists:edu_classes,id'],
            'lesson_title' => ['required', 'string', 'max:255'],
            'lesson_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'vocabulary' => ['nullable', 'string', 'max:5000'],
            'grammar' => ['nullable', 'string', 'max:5000'],
            'activities' => ['nullable', 'string', 'max:5000'],
            'homework' => ['nullable', 'string', 'max:5000'],
            'lesson_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_room_id.required' => 'Lớp học là bắt buộc.',
            'lesson_title.required' => 'Tiêu đề buổi học là bắt buộc.',
            'lesson_date.required' => 'Ngày học là bắt buộc.',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_room_id' => ['description' => 'Class id.', 'example' => 1],
            'lesson_title' => ['description' => 'Lesson title.', 'example' => 'My Family'],
            'lesson_date' => ['description' => 'Lesson date (Y-m-d).', 'example' => '2026-07-01'],
            'start_time' => ['description' => 'Start time (H:i).', 'example' => '08:00'],
            'end_time' => ['description' => 'End time (H:i).', 'example' => '10:00'],
            'room_id' => ['description' => 'Room id.', 'example' => 1],
            'teacher_id' => ['description' => 'Teacher id.', 'example' => 1],
        ];
    }
}
