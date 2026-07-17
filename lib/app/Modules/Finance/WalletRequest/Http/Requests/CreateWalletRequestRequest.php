<?php

namespace App\Modules\Finance\WalletRequest\Http\Requests;

use App\Modules\Finance\WalletRequest\Enums\WalletRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWalletRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', Rule::in(WalletRequestType::values())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Vui lòng chọn loại yêu cầu.',
            'request_type.in' => 'Loại yêu cầu không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.gt' => 'Số tiền phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'request_type' => ['description' => 'deposit|withdraw. Rút tiền dùng tài khoản ngân hàng đã lưu trong hồ sơ giáo viên.', 'example' => 'withdraw'],
            'amount' => ['description' => 'Số tiền.', 'example' => 500000],
            'note' => ['description' => 'Ghi chú (tùy chọn).', 'example' => 'Rút thu nhập tháng 7'],
        ];
    }
}
