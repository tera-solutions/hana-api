<?php

namespace App\Modules\Education\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_class_id' => ['required', 'integer', 'exists:edu_classes,id'],
            'transfer_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_class_id.required' => 'Vui lòng chọn lớp đích.',
            'to_class_id.exists' => 'Lớp đích không tồn tại.',
            'transfer_date.required' => 'Vui lòng nhập ngày chuyển lớp.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'to_class_id' => ['description' => 'ID lớp đích (cùng khóa học, còn chỗ trống).', 'example' => 2],
            'transfer_date' => ['description' => 'Ngày chuyển lớp (Y-m-d).', 'example' => '2026-08-15'],
            'reason' => ['description' => 'Lý do chuyển lớp.', 'example' => 'Đổi lịch học.'],
        ];
    }
}
