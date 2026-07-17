<?php

namespace App\Modules\Finance\WalletRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectWalletRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.required' => 'Vui lòng nhập lý do từ chối.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reject_reason' => ['description' => 'Lý do từ chối yêu cầu.', 'example' => 'Không đủ thông tin xác minh.'],
        ];
    }
}
