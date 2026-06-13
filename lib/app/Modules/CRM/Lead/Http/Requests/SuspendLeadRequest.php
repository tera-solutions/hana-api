<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Suspend a lead (lead.md §6 "Ngừng khách hàng").
 */
class SuspendLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for suspending the lead.', 'example' => 'Không còn nhu cầu'],
            'note' => ['description' => 'Additional note.', 'example' => 'Sẽ liên hệ lại sau 3 tháng'],
        ];
    }
}
