<?php

namespace App\Modules\Finance\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordFromInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:fin_wallets,id'],
            'invoice_id' => ['required', 'integer', 'exists:fin_invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.exists' => 'Ví không tồn tại.',
            'invoice_id.required' => 'Hóa đơn là bắt buộc.',
            'invoice_id.exists' => 'Hóa đơn không tồn tại.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'wallet_id' => ['description' => 'ID ví.', 'example' => 1],
            'invoice_id' => ['description' => 'Hóa đơn cần ghi nhận thanh toán từ ví.', 'example' => 1],
            'amount' => ['description' => 'Số tiền trừ vào ví cho hóa đơn (> 0).', 'example' => 2000000],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Trừ ví thanh toán hóa đơn #1.'],
        ];
    }
}
