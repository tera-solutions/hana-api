<?php

namespace App\Modules\Finance\BusinessBankAccount\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['sometimes', 'required', 'string', 'max:255'],
            'bank_code' => ['sometimes', 'required', 'string', 'max:50'],
            'account_number' => ['sometimes', 'required', 'string', 'max:255'],
            'account_holder' => ['sometimes', 'required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'bank_name' => ['description' => 'Tên ngân hàng.', 'example' => 'MB Bank'],
            'bank_code' => ['description' => 'Mã ngân hàng theo VietQR.', 'example' => '970422'],
            'account_number' => ['description' => 'Số tài khoản.', 'example' => '0123456789'],
            'account_holder' => ['description' => 'Tên chủ tài khoản.', 'example' => 'CONG TY TNHH HANA ENGLISH'],
            'branch' => ['description' => 'Chi nhánh (tùy chọn).'],
            'is_default' => ['description' => 'Đặt làm tài khoản mặc định trên hóa đơn.', 'example' => true],
        ];
    }
}
