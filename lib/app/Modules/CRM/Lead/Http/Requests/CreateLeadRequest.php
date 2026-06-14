<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use App\Enums\Shared\Gender;
use App\Enums\Shared\GuardianRelation;
use App\Modules\CRM\Lead\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a lead with its guardians, linked students, tags and courses (lead.md §3).
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
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
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
            'status' => ['nullable', 'string', Rule::in(LeadStatus::values())],
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
            'students.*.relationship' => ['nullable', 'string', Rule::in(GuardianRelation::values())],
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
            'source' => ['description' => 'Lead source.', 'example' => 'facebook'],
            'status' => ['description' => 'Initial status (defaults to pending): pending|verified|studying|inactive.', 'example' => 'pending'],
            'owner_id' => ['description' => 'Assigned staff (user) id.', 'example' => 1],
            'business_id' => ['description' => 'Business id.', 'example' => 1],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'note' => ['description' => 'Note.', 'example' => 'Tư vấn qua Zalo'],
            'tag_ids' => ['description' => 'Tag ids.', 'example' => [1, 2]],
            'course_ids' => ['description' => 'Interested course ids.', 'example' => [1]],
            'guardians' => ['description' => 'Guardians to create alongside the lead.', 'example' => []],
            'guardians[].full_name' => ['description' => 'Guardian full name.', 'example' => 'Nguyễn Văn B'],
            'guardians[].relationship' => ['description' => 'Relationship to the lead.', 'example' => 'Bố'],
            'guardians[].phone' => ['description' => 'Guardian phone (unique within this lead).', 'example' => '0907654321'],
            'guardians[].email' => ['description' => 'Guardian email.', 'example' => 'b@example.com'],
            'students' => ['description' => 'Existing students to link.', 'example' => []],
            'students[].student_id' => ['description' => 'Active student id.', 'example' => 1],
            'students[].relationship' => ['description' => 'father|mother|guardian|grandfather|grandmother|uncle|aunt|other.', 'example' => 'father'],
        ];
    }
}
