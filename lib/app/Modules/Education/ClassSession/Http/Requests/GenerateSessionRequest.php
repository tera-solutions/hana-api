<?php

namespace App\Modules\Education\ClassSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'override' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.required' => 'Vui lòng nhập ngày bắt đầu.',
            'to_date.required' => 'Vui lòng nhập ngày kết thúc.',
            'to_date.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'from_date' => ['description' => 'Sinh buổi học từ ngày (Y-m-d).', 'example' => '2026-07-01'],
            'to_date' => ['description' => 'Sinh buổi học đến ngày (Y-m-d).', 'example' => '2026-09-30'],
            'override' => ['description' => 'Ghi đè (xóa) các buổi chưa chốt điểm danh trong khoảng ngày trước khi sinh.', 'example' => false],
        ];
    }
}
