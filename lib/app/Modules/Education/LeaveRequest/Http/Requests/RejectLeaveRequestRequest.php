<?php

namespace App\Modules\Education\LeaveRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Lý do từ chối là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'rejection_reason' => ['description' => 'Lý do từ chối đơn.', 'example' => 'Không đủ điều kiện nghỉ.'],
        ];
    }
}
