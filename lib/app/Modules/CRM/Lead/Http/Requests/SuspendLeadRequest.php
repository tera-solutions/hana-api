<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Suspend a lead (lead.md §6 "Ngừng khách hàng").
 *
 * @bodyParam reason string required Reason for stopping. Example: Không còn nhu cầu
 * @bodyParam note string Additional note.
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
}
