<?php

namespace App\Modules\Education\LeaveRequest\Http\Requests;

use App\Modules\Education\LeaveRequest\Enums\MakeupStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleMakeupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'makeup_lesson_id' => ['required', 'integer', 'exists:edu_lessons,id'],
            'status' => ['nullable', Rule::in([MakeupStatus::Scheduled->value, MakeupStatus::Completed->value])],
        ];
    }

    public function messages(): array
    {
        return [
            'makeup_lesson_id.required' => 'Buổi học bù là bắt buộc.',
            'makeup_lesson_id.exists' => 'Buổi học bù không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'makeup_lesson_id' => ['description' => 'Buổi học dùng để học bù.', 'example' => 5],
            'status' => ['description' => 'Trạng thái học bù: scheduled | completed (mặc định scheduled).', 'example' => 'scheduled'],
        ];
    }
}
