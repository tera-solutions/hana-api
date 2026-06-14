<?php

namespace App\Modules\Education\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Lý do hủy ghi danh.', 'example' => 'Học viên không tiếp tục.'],
        ];
    }
}
