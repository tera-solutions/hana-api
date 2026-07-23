<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use App\Modules\CRM\Lead\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Move a lead through the care pipeline (pending/verified/consulting/studying).
 * "inactive" is out of scope here — use suspend/restore, which also record a reason.
 */
class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required', 'string',
                Rule::in(array_diff(LeadStatus::values(), [LeadStatus::Inactive->value])),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status' => ['description' => 'pending|verified|consulting|studying.', 'example' => 'consulting'],
            'note' => ['description' => 'Optional note recorded in the lead history.', 'example' => 'Đã hẹn tư vấn 22/07'],
        ];
    }
}
