<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordFromPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:fin_wallets,id'],
            'payment_id' => ['required', 'integer', 'exists:fin_payments,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Ví không tồn tại.',
            'payment_id.required' => 'Đơn thanh toán là bắt buộc.',
            'payment_id.exists' => 'Đơn thanh toán không tồn tại.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví.', 'example' => 1],
            'payment_id' => ['description' => 'Đơn thanh toán cần ghi nhận vào ví.', 'example' => 1],
            'amount' => ['description' => 'Số tiền cộng vào ví từ đơn thanh toán (> 0).', 'example' => 1000000],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Ghi nhận số dư từ đơn thanh toán #1.'],
        ];
    }
}
