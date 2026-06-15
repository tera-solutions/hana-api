<?php

namespace App\Modules\Finance\Debt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Raise a write-off request for an uncollectible debt (debt.md §XII).
 */
class WriteoffDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Số tiền xóa nợ phải lớn hơn 0.',
            'reason.required' => 'Vui lòng nhập lý do xóa nợ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'amount' => ['description' => 'Amount to write off (defaults to the full outstanding).', 'example' => 1000000],
            'reason' => ['description' => 'Reason for the write-off.', 'example' => 'Học viên bỏ học, không liên hệ được'],
            'note' => ['description' => 'Optional note.', 'example' => ''],
        ];
    }
}
