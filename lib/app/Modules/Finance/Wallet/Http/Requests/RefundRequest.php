<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
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
            'reference_transaction_id' => ['required', 'integer', 'exists:fin_wallet_transactions,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Ví không tồn tại.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
            'reference_transaction_id.required' => 'Giao dịch gốc là bắt buộc.',
            'reference_transaction_id.exists' => 'Giao dịch gốc không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví hoàn tiền.', 'example' => 1],
            'amount' => ['description' => 'Số tiền hoàn (> 0, không vượt số đã thanh toán).', 'example' => 500000],
            'reference_transaction_id' => ['description' => 'Giao dịch thanh toán gốc cần hoàn (BR008).', 'example' => 5],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Hoàn tiền hủy khóa học.'],
        ];
    }
}
