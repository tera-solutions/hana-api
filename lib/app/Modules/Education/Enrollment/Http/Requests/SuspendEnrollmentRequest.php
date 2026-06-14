<?php

namespace App\Modules\Education\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Vui lòng nhập ngày bắt đầu bảo lưu.',
            'end_date.required' => 'Vui lòng nhập ngày kết thúc bảo lưu.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'reason.required' => 'Vui lòng nhập lý do bảo lưu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'start_date' => ['description' => 'Ngày bắt đầu bảo lưu (Y-m-d).', 'example' => '2026-08-01'],
            'end_date' => ['description' => 'Ngày kết thúc bảo lưu (Y-m-d).', 'example' => '2026-09-01'],
            'reason' => ['description' => 'Lý do bảo lưu.', 'example' => 'Học viên đi công tác.'],
        ];
    }
}
