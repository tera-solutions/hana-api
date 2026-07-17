<?php

namespace App\Modules\HR\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:hr_teachers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Vui lòng chọn giáo viên.',
            'month.required' => 'Vui lòng chọn tháng.',
            'year.required' => 'Vui lòng chọn năm.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teacher_id' => ['description' => 'Giáo viên.', 'example' => 1],
            'month' => ['description' => 'Tháng (1-12).', 'example' => 7],
            'year' => ['description' => 'Năm.', 'example' => 2026],
            'bonus' => ['description' => 'Thưởng thêm (tùy chọn, giữ giá trị cũ nếu bỏ trống khi tính lại).', 'example' => 500000],
            'penalty' => ['description' => 'Phạt trừ (tùy chọn, giữ giá trị cũ nếu bỏ trống khi tính lại).', 'example' => 0],
        ];
    }
}
