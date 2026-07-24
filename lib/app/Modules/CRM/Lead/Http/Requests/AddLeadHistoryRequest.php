<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Manually log a care interaction against a lead (note / call / appointment) —
 * distinct from the automatic history entries LeadService writes on
 * create/update/status-change/suspend/restore/convert.
 */
class AddLeadHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['note', 'call', 'appointment'])],
            'content' => ['required', 'string', 'max:2000'],
            'next_appointment' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại tương tác.',
            'type.in' => 'Loại tương tác không hợp lệ.',
            'content.required' => 'Vui lòng nhập nội dung.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => ['description' => 'note|call|appointment.', 'example' => 'call'],
            'content' => ['description' => 'Nội dung ghi chú.', 'example' => 'Đã gửi bảng giá qua Zalo'],
            'next_appointment' => ['description' => 'Lịch hẹn tư vấn tiếp theo (nếu có).', 'example' => '2026-07-22T14:00:00Z'],
        ];
    }
}
