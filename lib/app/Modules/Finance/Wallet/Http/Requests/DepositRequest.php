<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
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
            'payment_method' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Ví là bắt buộc.',
            'wallet_id.exists' => 'Ví không tồn tại.',
            'amount.required' => 'Số tiền là bắt buộc.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví cần nạp.', 'example' => 1],
            'amount' => ['description' => 'Số tiền nạp (> 0).', 'example' => 500000],
            'payment_method' => ['description' => 'Phương thức nạp.', 'example' => 'cash'],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Nạp tiền mặt tại quầy.'],
        ];
    }
}
