<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a lead with its guardians, linked students, tags and courses (lead.md §3).
 *
 * @bodyParam name string required Full name. Example: Nguyễn Văn A
 * @bodyParam gender string male|female|other. Example: male
 * @bodyParam dob date Date of birth (<= today). Example: 2010-03-20
 * @bodyParam email string Contact email. Example: a@example.com
 * @bodyParam phone string required Contact phone (unique among active leads). Example: 0901234567
 * @bodyParam source string required Lead source. Example: facebook
 * @bodyParam status string Initial status (defaults to pending). Example: pending
 * @bodyParam owner_id integer required Assigned staff (user) id. Example: 1
 * @bodyParam business_id integer Business id. Example: 1
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam note string Note.
 * @bodyParam tag_ids integer[] Tag ids. Example: [1,2]
 * @bodyParam course_ids integer[] Interested course ids. Example: [1]
 * @bodyParam guardians object[] Guardians to create.
 * @bodyParam guardians[].full_name string required Guardian name. Example: Nguyễn Văn B
 * @bodyParam guardians[].relationship string required Relationship. Example: Bố
 * @bodyParam guardians[].phone string required Guardian phone. Example: 0907654321
 * @bodyParam guardians[].email string Guardian email.
 * @bodyParam students object[] Existing students to link.
 * @bodyParam students[].student_id integer required Student id. Example: 1
 * @bodyParam students[].relationship string Relationship. Example: father
 */
class CreateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/',
                // Must not collide with an active (non-inactive) lead.
                Rule::unique('crm_leads', 'phone')->where(fn ($q) => $q
                    ->where('status', '<>', 'inactive')
                    ->whereNull('deleted_at')),
            ],

            'source' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,verified,studying,inactive'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'note' => ['nullable', 'string', 'max:2000'],

            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:crm_tags,id'],

            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:edu_courses,id'],

            'guardians' => ['nullable', 'array'],
            'guardians.*.full_name' => ['required', 'string', 'max:255'],
            'guardians.*.relationship' => ['required', 'string', 'max:100'],
            // Guardian phones must be unique within this lead's payload.
            'guardians.*.phone' => ['required', 'string', 'max:20', 'distinct'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],

            'students' => ['nullable', 'array'],
            'students.*.student_id' => ['required', 'integer', 'distinct', 'exists:edu_students,id'],
            'students.*.relationship' => ['nullable', 'string', 'in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Số điện thoại đã tồn tại ở một khách hàng đang hoạt động.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'dob.before_or_equal' => 'Ngày sinh phải nhỏ hơn hoặc bằng ngày hiện tại.',
            'guardians.*.phone.distinct' => 'Số điện thoại người giám hộ bị trùng trong cùng một khách hàng.',
        ];
    }
}
