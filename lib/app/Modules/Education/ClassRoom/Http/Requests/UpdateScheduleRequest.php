<?php

namespace App\Modules\Education\ClassRoom\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekday' => ['sometimes', 'integer', 'between:1,7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
        ];
    }

    /**
     * Cross-field time check only when both ends are supplied — a partial update
     * sending just one of them must not fail on an unresolved `after:` reference.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if ($start && $end && $end <= $start) {
                $v->errors()->add('end_time', 'Giờ kết thúc phải sau giờ bắt đầu.');
            }
        });
    }

    public function bodyParameters(): array
    {
        return [
            'weekday' => ['description' => 'Thứ trong tuần (1=T2 … 7=CN).', 'example' => 5],
            'start_time' => ['description' => 'Giờ bắt đầu (HH:MM).', 'example' => '19:00'],
            'end_time' => ['description' => 'Giờ kết thúc (HH:MM).', 'example' => '20:30'],
        ];
    }
}
