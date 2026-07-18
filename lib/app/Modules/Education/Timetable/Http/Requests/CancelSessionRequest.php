<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do hủy buổi học.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Lý do hủy buổi học.', 'example' => 'Nghỉ lễ.'],
        ];
    }
}
