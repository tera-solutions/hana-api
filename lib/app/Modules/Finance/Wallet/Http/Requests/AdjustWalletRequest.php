<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:fin_wallets,id'],
            'adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'], // BR010
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Ví không tồn tại.',
            'adjustment_type.required' => 'Loại điều chỉnh là bắt buộc.',
            'adjustment_type.in' => 'Loại điều chỉnh phải là increase hoặc decrease.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
            'reason.required' => 'Lý do điều chỉnh là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví cần điều chỉnh.', 'example' => 1],
            'adjustment_type' => ['description' => 'Loại điều chỉnh: increase | decrease.', 'example' => 'increase'],
            'amount' => ['description' => 'Số tiền điều chỉnh (> 0).', 'example' => 100000],
            'reason' => ['description' => 'Lý do điều chỉnh (bắt buộc).', 'example' => 'Bù trừ sai lệch đối soát.'],
        ];
    }
}
