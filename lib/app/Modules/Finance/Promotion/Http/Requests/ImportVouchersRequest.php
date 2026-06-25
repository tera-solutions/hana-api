<?php

namespace App\Modules\Finance\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Import vouchers for a promotion from an uploaded spreadsheet. The file is referenced by
 * its uploaded media id; per-row parsing/validation and partial-success live in the
 * service. Defaults apply to rows that omit a value.
 */
class ImportVouchersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['required', 'integer', 'exists:media,id'],
            'file_name' => ['nullable', 'string', 'max:255'],

            'default_usage_limit' => ['nullable', 'integer', 'min:1'],
            'default_expired_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'Tập tin import là bắt buộc.',
            'file_id.exists' => 'Tập tin import không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file_id' => ['description' => 'Uploaded media id of the spreadsheet (Voucher Code | Usage Limit | Expired At).', 'example' => 10],
            'file_name' => ['description' => 'Original file name (optional, for display).', 'example' => 'vouchers.xlsx'],
            'default_usage_limit' => ['description' => 'Usage limit for rows that omit one (default 1).', 'example' => 1],
            'default_expired_at' => ['description' => 'Expiry for rows that omit one (default promotion end date).', 'example' => '2026-08-31'],
        ];
    }
}
