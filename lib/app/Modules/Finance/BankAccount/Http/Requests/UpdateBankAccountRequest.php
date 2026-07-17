<?php

namespace App\Modules\Finance\BankAccount\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:255'],
            'bank_account_holder' => ['required', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'Vui lòng nhập tên ngân hàng.',
            'bank_account_number.required' => 'Vui lòng nhập số tài khoản.',
            'bank_account_holder.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'bank_name' => ['description' => 'Tên ngân hàng.', 'example' => 'Vietcombank'],
            'bank_account_number' => ['description' => 'Số tài khoản.', 'example' => '0123456789'],
            'bank_account_holder' => ['description' => 'Tên chủ tài khoản.', 'example' => 'NGUYEN VAN A'],
            'bank_branch' => ['description' => 'Chi nhánh (tùy chọn).', 'example' => 'Chi nhánh Hà Nội'],
        ];
    }
}
