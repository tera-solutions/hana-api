<?php

namespace App\Modules\Finance\BusinessBankAccount\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBusinessBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_code' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:255'],
            'account_holder' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'Vui lòng nhập tên ngân hàng.',
            'bank_code.required' => 'Vui lòng chọn ngân hàng (mã VietQR).',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_holder.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'bank_name' => ['description' => 'Tên ngân hàng.', 'example' => 'MB Bank'],
            'bank_code' => ['description' => 'Mã ngân hàng theo VietQR (BIN hoặc tên viết tắt).', 'example' => '970422'],
            'account_number' => ['description' => 'Số tài khoản.', 'example' => '0123456789'],
            'account_holder' => ['description' => 'Tên chủ tài khoản (không dấu).', 'example' => 'CONG TY TNHH HANA ENGLISH'],
            'branch' => ['description' => 'Chi nhánh (tùy chọn).', 'example' => 'Chi nhánh Hà Nội'],
            'is_default' => ['description' => 'Đặt làm tài khoản mặc định trên hóa đơn.', 'example' => true],
            'business_id' => ['description' => 'Business id.', 'example' => 1],
        ];
    }
}
