<?php

namespace App\Modules\Finance\InvoiceConfig\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_generate' => ['required', 'boolean'],
            'billing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'due_days' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'billing_day.max' => 'Ngày lập hóa đơn phải từ 1 đến 28 (để hợp lệ với mọi tháng).',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'auto_generate' => ['description' => 'Bật/tắt tự động tạo hóa đơn hàng tháng.', 'example' => true],
            'billing_day' => ['description' => 'Ngày trong tháng để lập hóa đơn (1-28).', 'example' => 1],
            'due_days' => ['description' => 'Số ngày cho phép thanh toán kể từ ngày lập.', 'example' => 7],
        ];
    }
}
