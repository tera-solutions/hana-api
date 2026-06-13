<?php

namespace App\Modules\Education\ClassRoom\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'weekday.between' => 'Thứ trong tuần phải từ 1 (Thứ 2) đến 7 (Chủ nhật).',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'weekday' => ['description' => 'Thứ trong tuần (1=T2, 2=T3, …, 7=CN).', 'example' => 2],
            'start_time' => ['description' => 'Giờ bắt đầu (HH:MM).', 'example' => '19:00'],
            'end_time' => ['description' => 'Giờ kết thúc (HH:MM).', 'example' => '20:30'],
        ];
    }
}
