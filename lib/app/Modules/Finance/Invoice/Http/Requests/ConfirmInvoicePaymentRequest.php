<?php

namespace App\Modules\Finance\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(['bank_transfer', 'cash'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => 'Vui lòng chọn phương thức thanh toán.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'method' => ['description' => 'Phương thức: bank_transfer | cash.', 'example' => 'bank_transfer'],
            'note' => ['description' => 'Ghi chú.', 'example' => 'Đã CK qua Vietcombank'],
        ];
    }
}
