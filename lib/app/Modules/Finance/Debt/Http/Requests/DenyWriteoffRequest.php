<?php

namespace App\Modules\Finance\Debt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject a pending write-off request (debt.md §XII).
 */
class DenyWriteoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do từ chối.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for rejecting the write-off.', 'example' => 'Chưa đủ căn cứ xóa nợ'],
        ];
    }
}
