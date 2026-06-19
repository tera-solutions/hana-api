<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voucher_code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'voucher_code.required' => 'Mã voucher là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'voucher_code' => ['description' => 'Mã voucher cần kiểm tra.', 'example' => 'HANA2026AB'],
        ];
    }
}
