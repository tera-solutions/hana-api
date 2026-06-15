<?php

namespace App\Modules\Finance\Invoice\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a receipt/disbursement against an invoice (invoice.md §X).
 */
class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
            'method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => ['description' => 'Payment amount (<= remaining balance).', 'example' => 1000000],
            'method' => ['description' => 'cash|transfer|card|wallet|other.', 'example' => 'transfer'],
            'transaction_id' => ['description' => 'External transaction reference.', 'example' => 'TXN-001'],
            'note' => ['description' => 'Free-text note.', 'example' => 'Thu học phí tháng 6'],
            'paid_at' => ['description' => 'Payment time (defaults to now).', 'example' => '2026-06-15 10:00:00'],
        ];
    }
}
