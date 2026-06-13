<?php

namespace App\Modules\CRM\ParentStudent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    public function bodyParameters(): array
    {
        return [
            'parent_id' => [
                'description' => 'Active parent id.',
                'example' => 1,
            ],
            'student_id' => [
                'description' => 'Active student id.',
                'example' => 1,
            ],
            'relation' => [
                'description' => 'father|mother|guardian|grandfather|grandmother|uncle|aunt|other.',
                'example' => 'father',
            ],
            'is_primary_contact' => [
                'description' => 'Main contact.',
                'example' => true,
            ],
            'is_billing_contact' => [
                'description' => 'Receives invoices.',
                'example' => true,
            ],
            'is_pickup_authorized' => [
                'description' => 'Authorized to pick up the student.',
                'example' => false,
            ],
            'note' => [
                'description' => 'Note.',
            ],
        ];
    }
}
