<?php

namespace App\Modules\Finance\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Partial update of a fund. Code, business and balance are immutable (balance
 * moves only through confirmed payments — BR-03).
 */
class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function bodyParameters(): array
    {
        return [
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'name' => ['description' => 'Fund name.', 'example' => 'Tiền mặt trung tâm'],
            'currency' => ['description' => 'Currency code.', 'example' => 'VND'],
            'bank_name' => ['description' => 'Bank name.', 'example' => 'Vietcombank'],
            'account_number' => ['description' => 'Account number.', 'example' => '0123456789'],
            'status' => ['description' => 'active|inactive.', 'example' => 'active'],
            'note' => ['description' => 'Note.', 'example' => 'Cập nhật ghi chú'],
        ];
    }
}
