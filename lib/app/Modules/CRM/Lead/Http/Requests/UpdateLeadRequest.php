<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a lead. Code, business and status are immutable and ignored if sent;
 * status is changed via suspend/restore (lead.md §4).
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

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Full name.', 'example' => 'Nguyễn Văn A'],
            'gender' => ['description' => 'male|female|other.', 'example' => 'male'],
            'dob' => ['description' => 'Date of birth (<= today).', 'example' => '2010-03-20'],
            'email' => ['description' => 'Contact email.', 'example' => 'a@example.com'],
            'phone' => ['description' => 'Contact phone (unique among active leads).', 'example' => '0901234567'],
            'source' => ['description' => 'Lead source.', 'example' => 'zalo'],
            'owner_id' => ['description' => 'Assigned staff (user) id.', 'example' => 1],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'note' => ['description' => 'Note.', 'example' => 'Đã tư vấn lần 2'],
            'tag_ids' => ['description' => 'Tag ids (replaces existing).', 'example' => [1, 2]],
            'course_ids' => ['description' => 'Interested course ids (replaces existing).', 'example' => [1]],
            'guardians' => ['description' => 'Guardians (replaces all existing when provided).', 'example' => []],
            'guardians[].full_name' => ['description' => 'Guardian full name.', 'example' => 'Nguyễn Văn B'],
            'guardians[].relationship' => ['description' => 'Relationship to the lead.', 'example' => 'Bố'],
            'guardians[].phone' => ['description' => 'Guardian phone.', 'example' => '0907654321'],
            'guardians[].email' => ['description' => 'Guardian email.', 'example' => 'b@example.com'],
            'students' => ['description' => 'Linked students (replaces all existing when provided).', 'example' => []],
            'students[].student_id' => ['description' => 'Active student id.', 'example' => 1],
            'students[].relationship' => ['description' => 'father|mother|guardian|grandfather|grandmother|uncle|aunt|other.', 'example' => 'father'],
        ];
    }
}
