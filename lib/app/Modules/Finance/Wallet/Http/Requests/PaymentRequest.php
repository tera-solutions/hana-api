<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:fin_wallets,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'invoice_id' => ['nullable', 'integer', 'exists:fin_invoices,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Ví là bắt buộc.',
            'wallet_id.exists' => 'Ví không tồn tại.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví thanh toán.', 'example' => 1],
            'amount' => ['description' => 'Số tiền thanh toán (> 0).', 'example' => 2000000],
            'invoice_id' => ['description' => 'Hóa đơn liên quan (tùy chọn).', 'example' => 1],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Thanh toán học phí.'],
        ];
    }
}
