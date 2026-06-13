<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Link an existing student to a lead (lead.md §9).
 */
class CreateLeadStudentRequest extends FormRequest
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
            'student_id' => [
                'required', 'integer',
                Rule::exists('edu_students', 'id')->where('status', 'active')->whereNull('deleted_at'),
                // A student may be linked to a lead at most once (non-deleted).
                Rule::unique('crm_lead_students', 'student_id')->where(fn ($q) => $q
                    ->where('lead_id', $this->route('leadId'))
                    ->whereNull('deleted_at')),
            ],
            'relationship' => [
                'nullable', 'string',
                'in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.exists' => 'Khách hàng không tồn tại.',
            'student_id.exists' => 'Học viên không tồn tại hoặc không hoạt động.',
            'student_id.unique' => 'Học viên này đã được liên kết với khách hàng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_id' => ['description' => 'Active student id (may be linked once per lead).', 'example' => 1],
            'relationship' => ['description' => 'father|mother|guardian|grandfather|grandmother|uncle|aunt|other.', 'example' => 'father'],
        ];
    }
}
