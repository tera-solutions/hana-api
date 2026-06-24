<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVouchersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expired_at' => ['nullable', 'date'],
            'prefix' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Số lượng voucher là bắt buộc.',
            'quantity.max' => 'Tối đa 1000 voucher mỗi lần sinh.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'quantity' => ['description' => 'Số lượng voucher cần sinh.', 'example' => 100],
            'usage_limit' => ['description' => 'Số lần dùng tối đa mỗi voucher (mặc định 1).', 'example' => 1],
            'expired_at' => ['description' => 'Ngày hết hạn (mặc định theo ngày kết thúc chương trình).', 'example' => '2026-08-31'],
            'prefix' => ['description' => 'Tiền tố mã voucher.', 'example' => 'HANA'],
        ];
    }
}
