<?php

namespace App\Modules\Finance\Debt\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Collect a debt by recording a payment against the invoice (debt.md §X).
 */
class CollectDebtRequest extends FormRequest
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
            'account_id' => ['nullable', 'integer', 'exists:fin_accounts,id'],
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
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => ['description' => 'Amount collected (<= outstanding).', 'example' => 1000000],
            'method' => ['description' => 'cash|transfer|card|wallet|other.', 'example' => 'transfer'],
            'account_id' => ['description' => 'Fund (quỹ) receiving the money.', 'example' => 1],
            'transaction_id' => ['description' => 'External transaction reference.', 'example' => 'FT24009'],
            'note' => ['description' => 'Note.', 'example' => 'Thu hồi công nợ tháng 6'],
            'paid_at' => ['description' => 'Payment time (default now).', 'example' => '2026-06-15 10:00:00'],
        ];
    }
}
