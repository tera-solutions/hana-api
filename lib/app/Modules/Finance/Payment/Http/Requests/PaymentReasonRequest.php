<?php

namespace App\Modules\Finance\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request for state transitions that require a reason (cancel/reverse).
 */
class PaymentReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for the action.', 'example' => 'Giao dịch nhầm'],
            'note' => ['description' => 'Optional note.', 'example' => ''],
        ];
    }
}
