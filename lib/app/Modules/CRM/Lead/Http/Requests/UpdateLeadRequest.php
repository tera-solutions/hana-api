<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a lead. Code, business and status are immutable and ignored if sent;
 * status is changed via suspend/restore (lead.md §4).
 *
 * @bodyParam name string Full name. Example: Nguyễn Văn A
 * @bodyParam gender string male|female|other. Example: male
 * @bodyParam dob date Date of birth (<= today). Example: 2010-03-20
 * @bodyParam email string Contact email. Example: a@example.com
 * @bodyParam phone string Contact phone (unique among active leads). Example: 0901234567
 * @bodyParam source string Lead source. Example: facebook
 * @bodyParam owner_id integer Assigned staff (user) id. Example: 1
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam note string Note.
 * @bodyParam tag_ids integer[] Tag ids (replaces existing). Example: [1,2]
 * @bodyParam course_ids integer[] Interested course ids (replaces existing). Example: [1]
 * @bodyParam guardians object[] Guardians (replaces existing when provided).
 * @bodyParam students object[] Linked students (replaces existing when provided).
 */
class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => [
                'sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/',
                Rule::unique('crm_leads', 'phone')->ignore($id)->where(fn ($q) => $q
                    ->where('status', '<>', 'inactive')
                    ->whereNull('deleted_at')),
            ],

            'source' => ['sometimes', 'required', 'string', 'max:255'],
            'owner_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'note' => ['nullable', 'string', 'max:2000'],

            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:crm_tags,id'],

            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:edu_courses,id'],

            'guardians' => ['nullable', 'array'],
            'guardians.*.full_name' => ['required', 'string', 'max:255'],
            'guardians.*.relationship' => ['required', 'string', 'max:100'],
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
