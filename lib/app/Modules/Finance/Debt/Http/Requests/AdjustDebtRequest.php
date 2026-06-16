<?php

namespace App\Modules\Finance\Debt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a debt correction or late discount against an invoice (debt.md §XI).
 */
class AdjustDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', 'string', Rule::in(['correction', 'discount'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_type.in' => 'Loại điều chỉnh phải là correction hoặc discount.',
            'amount.gt' => 'Số tiền điều chỉnh phải lớn hơn 0.',
            'reason.required' => 'Vui lòng nhập lý do điều chỉnh.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'adjustment_type' => ['description' => 'correction (sửa hóa đơn) | discount (giảm giá sau phát hành).', 'example' => 'discount'],
            'amount' => ['description' => 'Amount to reduce from the invoice total.', 'example' => 200000],
            'reason' => ['description' => 'Reason for the adjustment.', 'example' => 'Giảm giá cho học viên cũ'],
            'note' => ['description' => 'Optional note.', 'example' => ''],
        ];
    }
}
