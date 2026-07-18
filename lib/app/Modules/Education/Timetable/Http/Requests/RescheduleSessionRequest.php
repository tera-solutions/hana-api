<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_date.required' => 'Vui lòng chọn ngày học mới.',
            'start_time.required' => 'Vui lòng nhập giờ bắt đầu mới.',
            'end_time.required' => 'Vui lòng nhập giờ kết thúc mới.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'session_date' => ['description' => 'Ngày học mới (Y-m-d).', 'example' => '2026-07-21'],
            'start_time' => ['description' => 'Giờ bắt đầu mới (H:i).', 'example' => '18:00'],
            'end_time' => ['description' => 'Giờ kết thúc mới (H:i).', 'example' => '19:30'],
            'reason' => ['description' => 'Lý do dời lịch.', 'example' => 'Trùng lịch phòng, dời sang ngày khác.'],
        ];
    }
}
