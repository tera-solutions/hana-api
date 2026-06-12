<?php

namespace App\Modules\CRM\Parent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam suspend_date date required Suspension date. Example: 2026-06-12
 * @bodyParam reason string required Reason for suspension. Example: Ngừng liên hệ
 * @bodyParam note string Additional note.
 */
class SuspendParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'suspend_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
