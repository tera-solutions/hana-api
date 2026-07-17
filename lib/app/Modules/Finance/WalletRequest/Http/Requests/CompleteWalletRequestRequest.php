<?php

namespace App\Modules\Finance\WalletRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWalletRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'note' => ['description' => 'Ghi chú xác nhận (vd. mã giao dịch chuyển khoản).', 'example' => 'Đã chuyển khoản, mã GD FT26071712345'],
        ];
    }
}
