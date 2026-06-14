<?php

namespace App\Modules\CRM\Parent\Http\Requests;

use App\Enums\Shared\Gender;
use App\Enums\Shared\GuardianRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parent id, code, business and status are immutable and ignored if sent.
 */
class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'avatar' => ['nullable', 'string', 'max:1000'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],

            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],

            'students' => ['nullable', 'array'],
            'students.*.student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'students.*.relation' => ['nullable', 'string', Rule::in(GuardianRelation::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'dob.before_or_equal' => 'Ngày sinh phải nhỏ hơn hoặc bằng ngày hiện tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Full name.',
                'example' => 'Robert Smith',
            ],
            'gender' => [
                'description' => 'male|female|other.',
                'example' => 'male',
            ],
            'dob' => [
                'description' => 'Date of birth (<= today).',
                'example' => '1985-03-20',
            ],
            'avatar' => [
                'description' => 'Avatar URL.',
            ],
            'email' => [
                'description' => 'Contact email.',
                'example' => 'robert@example.com',
            ],
            'phone' => [
                'description' => 'Contact phone.',
                'example' => '0922222222',
            ],
            'address' => [
                'description' => 'Address.',
                'example' => '123 Le Loi',
            ],
            'province' => [
                'description' => 'Province / city.',
                'example' => 'Ho Chi Minh',
            ],
            'district' => [
                'description' => 'District.',
                'example' => 'District 7',
            ],
            'branch_id' => [
                'description' => 'Branch id.',
                'example' => 1,
            ],
            'occupation' => [
                'description' => 'Occupation.',
                'example' => 'Engineer',
            ],
            'company' => [
                'description' => 'Company.',
                'example' => 'ABC Corp',
            ],
            'note' => [
                'description' => 'Note.',
            ],
            'students' => [
                'description' => 'Students to link (replaces existing links).',
                'type' => 'object[]',
            ],
            'students[].student_id' => [
                'description' => 'Existing student id.',
                'example' => 1,
            ],
            'students[].relation' => [
                'description' => 'father|mother|guardian|grandfather|grandmother|other.',
                'example' => 'father',
            ],
        ];
    }
}
