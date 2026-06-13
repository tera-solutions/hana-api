<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use App\Modules\CRM\Lead\Models\LeadGuardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a guardian. The owning lead is immutable and ignored if sent.
 *
 * @bodyParam full_name string Guardian full name. Example: Nguyễn Văn A
 * @bodyParam relationship string Relationship to the lead. Example: Mẹ
 * @bodyParam phone string Phone number, unique within the lead. Example: 0901234567
 * @bodyParam email string Guardian email. Example: guardian@example.com
 */
class UpdateLeadGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $guardian = LeadGuardian::find($id);

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'relationship' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('crm_lead_guardians', 'phone')->ignore($id)->where(fn ($q) => $q
                    ->where('lead_id', $guardian?->lead_id)
                    ->whereNull('deleted_at')),
            ],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Số điện thoại người giám hộ đã tồn tại trong khách hàng này.',
        ];
    }
}
