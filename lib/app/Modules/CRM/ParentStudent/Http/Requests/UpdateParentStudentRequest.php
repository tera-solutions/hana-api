<?php

namespace App\Modules\CRM\ParentStudent\Http\Requests;

use App\Modules\CRM\ParentStudent\Models\ParentStudent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parent and Student are immutable and ignored if sent.
 *
 * @bodyParam relation string father|mother|guardian|grandfather|grandmother|uncle|aunt|other. Example: mother
 * @bodyParam is_primary_contact boolean Main contact. Example: true
 * @bodyParam is_billing_contact boolean Receives invoices. Example: true
 * @bodyParam is_pickup_authorized boolean Authorized to pick up the student. Example: false
 * @bodyParam note string Note.
 */
class UpdateParentStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $link = ParentStudent::find($id);

        return [
            'relation' => [
                'sometimes', 'required', 'string',
                'in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other',
                Rule::unique('crm_parent_student', 'relation')->ignore($id)->where(fn ($q) => $q
                    ->where('parent_id', $link?->parent_id)
                    ->where('student_id', $link?->student_id)
                    ->whereNull('deleted_at')),
            ],
            'is_primary_contact' => ['nullable', 'boolean'],
            'is_billing_contact' => ['nullable', 'boolean'],
            'is_pickup_authorized' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'relation.unique' => 'Quan hệ này đã tồn tại cho phụ huynh và học viên.',
        ];
    }
}
