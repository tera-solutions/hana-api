<?php

namespace App\Modules\Finance\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Refund part or all of a confirmed payment (payment.md §X).
 */
class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do hoàn tiền.',
            'amount.gt' => 'Số tiền hoàn phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => ['description' => 'Refund amount (defaults to the full payment amount).', 'example' => 500000],
            'reason' => ['description' => 'Reason for the refund.', 'example' => 'Học viên đóng dư'],
            'note' => ['description' => 'Optional note.', 'example' => ''],
        ];
    }
}
