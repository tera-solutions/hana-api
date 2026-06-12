<?php

namespace App\Modules\CRM\ParentStudent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam parent_id integer required Active parent id. Example: 1
 * @bodyParam student_id integer required Active student id. Example: 1
 * @bodyParam relation string required father|mother|guardian|grandfather|grandmother|uncle|aunt|other. Example: father
 * @bodyParam is_primary_contact boolean Main contact. Example: true
 * @bodyParam is_billing_contact boolean Receives invoices. Example: true
 * @bodyParam is_pickup_authorized boolean Authorized to pick up the student. Example: false
 * @bodyParam note string Note.
 */
class CreateParentStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'required', 'integer',
                Rule::exists('crm_parents', 'id')->where('status', 'active')->whereNull('deleted_at'),
            ],
            'student_id' => [
                'required', 'integer',
                Rule::exists('edu_students', 'id')->where('status', 'active')->whereNull('deleted_at'),
            ],
            'relation' => [
                'required', 'string',
                'in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other',
                // Unique per (parent, student, relation) among non-deleted rows.
                Rule::unique('crm_parent_student', 'relation')->where(fn ($q) => $q
                    ->where('parent_id', $this->input('parent_id'))
                    ->where('student_id', $this->input('student_id'))
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
            'parent_id.exists' => 'Phụ huynh không tồn tại hoặc không hoạt động.',
            'student_id.exists' => 'Học viên không tồn tại hoặc không hoạt động.',
            'relation.unique' => 'Quan hệ này đã tồn tại cho phụ huynh và học viên.',
        ];
    }
}
