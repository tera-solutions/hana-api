<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add a guardian to a lead (lead.md §3 / §8).
 */
class CreateLeadGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The lead id comes from the route, not the body — surface it for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['lead_id' => $this->route('leadId')]);
    }

    public function rules(): array
    {
        return [
            'lead_id' => [
                'required', 'integer',
                Rule::exists('crm_leads', 'id'),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => [
                'required', 'string', 'max:20',
                // Phone must be unique within the same lead (non-deleted).
                Rule::unique('crm_lead_guardians', 'phone')->where(fn ($q) => $q
                    ->where('lead_id', $this->route('leadId'))
                    ->whereNull('deleted_at')),
            ],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.exists' => 'Khách hàng không tồn tại.',
            'phone.unique' => 'Số điện thoại người giám hộ đã tồn tại trong khách hàng này.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'full_name' => ['description' => 'Guardian full name.', 'example' => 'Nguyễn Văn B'],
            'relationship' => ['description' => 'Relationship to the lead.', 'example' => 'Bố'],
            'phone' => ['description' => 'Phone number (unique within this lead).', 'example' => '0907654321'],
            'email' => ['description' => 'Guardian email.', 'example' => 'guardian@example.com'],
        ];
    }
}
