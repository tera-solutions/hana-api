<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_date.required' => 'Ngày học là bắt buộc.',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'lesson_date' => ['description' => 'New date (Y-m-d).', 'example' => '2026-07-05'],
            'start_time' => ['description' => 'New start time (H:i).', 'example' => '09:00'],
            'end_time' => ['description' => 'New end time (H:i).', 'example' => '11:00'],
            'room_id' => ['description' => 'New room id (defaults to current).', 'example' => 2],
        ];
    }
}
