<?php

namespace App\Modules\Education\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query validation for the room schedule-conflict check (room.md §11, BR006).
 */
class CheckRoomScheduleRequest extends FormRequest
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
            'ignore_session_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_date.required' => 'Ngày học là bắt buộc.',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc.',
            'end_time.required' => 'Giờ kết thúc là bắt buộc.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function queryParameters(): array
    {
        return [
            'lesson_date' => [
                'description' => 'Date to check (Y-m-d).',
                'example' => '2026-07-01',
            ],
            'start_time' => [
                'description' => 'Start time (H:i).',
                'example' => '08:00',
            ],
            'end_time' => [
                'description' => 'End time (H:i).',
                'example' => '10:00',
            ],
            'ignore_session_id' => [
                'description' => 'Session id to exclude (e.g. when rescheduling itself).',
                'example' => 5,
            ],
        ];
    }
}
