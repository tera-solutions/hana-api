<?php

namespace App\Modules\Finance\Account\Http\Requests;

use App\Modules\Finance\Account\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a fund / quỹ (payment.md §VI).
 */
class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(AccountType::values())],
            'currency' => ['nullable', 'string', 'max:10'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.required' => 'Vui lòng chọn trung tâm.',
            'name.required' => 'Vui lòng nhập tên quỹ.',
            'type.required' => 'Vui lòng chọn loại quỹ.',
            'type.in' => 'Loại quỹ không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'business_id' => ['description' => 'Business (trung tâm) id.', 'example' => 1],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'name' => ['description' => 'Fund name.', 'example' => 'Tiền mặt trung tâm'],
            'type' => ['description' => 'cash|bank|ewallet.', 'example' => 'cash'],
            'currency' => ['description' => 'Currency code (default VND).', 'example' => 'VND'],
            'balance' => ['description' => 'Opening balance.', 'example' => 0],
            'bank_name' => ['description' => 'Bank name (for bank accounts).', 'example' => 'Vietcombank'],
            'account_number' => ['description' => 'Bank/e-wallet account number.', 'example' => '0123456789'],
            'note' => ['description' => 'Note.', 'example' => 'Quỹ tiền mặt CN Q12'],
        ];
    }
}
