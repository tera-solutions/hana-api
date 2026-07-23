<?php

namespace App\Modules\Education\Student\Http\Requests;

use App\Enums\Shared\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', Rule::in(Gender::values())],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],

            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['required', 'integer', 'exists:sys_branches,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'enrollment_date' => ['required', 'date'],
            'admission_source' => ['nullable', 'string', 'max:255'],

            'address' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],

            'parents' => ['nullable', 'array'],
            'parents.*.parent_id' => ['nullable', 'integer', 'exists:crm_parents,id'],
            'parents.*.name' => ['required_without:parents.*.parent_id', 'nullable', 'string', 'max:255'],
            'parents.*.phone' => ['nullable', 'string', 'max:20'],
            'parents.*.email' => ['nullable', 'email', 'max:255'],
            'parents.*.relation' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'dob.before_or_equal' => 'Ngày sinh phải nhỏ hơn hoặc bằng ngày hiện tại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'parents.*.name.required_without' => 'Tên phụ huynh là bắt buộc khi không chọn phụ huynh có sẵn.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Full name.',
                'example' => 'Nguyen Van A',
            ],
            'dob' => [
                'description' => 'Date of birth (<= today).',
                'example' => '2010-05-12',
            ],
            'gender' => [
                'description' => 'male|female|other.',
                'example' => 'male',
            ],
            'avatar' => [
                'description' => 'Avatar URL.',
                'example' => 'https://cdn.hana.edu.vn/a.png',
            ],
            'nationality' => [
                'description' => 'Nationality.',
                'example' => 'Vietnam',
            ],
            'language' => [
                'description' => 'Native language.',
                'example' => 'Vietnamese',
            ],
            'email' => [
                'description' => 'Contact email.',
                'example' => 'a@gmail.com',
            ],
            'phone' => [
                'description' => 'Contact phone.',
                'example' => '0901234567',
            ],
            'business_id' => [
                'description' => 'Business id.',
                'example' => 1,
            ],
            'branch_id' => [
                'description' => 'Branch id.',
                'example' => 1,
            ],
            'level_id' => [
                'description' => 'Proficiency level id (edu_levels).',
                'example' => 1,
            ],
            'enrollment_date' => [
                'description' => 'Enrollment date.',
                'example' => '2026-06-01',
            ],
            'admission_source' => [
                'description' => 'Admission source.',
                'example' => 'Facebook',
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
            'school' => [
                'description' => 'School.',
                'example' => 'THPT Le Quy Don',
            ],
            'grade' => [
                'description' => 'Grade.',
                'example' => '9',
            ],
            'note' => [
                'description' => 'Note.',
            ],
            'parents' => [
                'description' => 'Parents/guardians to assign.',
                'type' => 'object[]',
            ],
            'parents[].parent_id' => [
                'description' => 'Existing parent id.',
                'example' => 1,
            ],
            'parents[].name' => [
                'description' => 'Name (required when no parent_id).',
                'example' => 'Tran Thi B',
            ],
            'parents[].phone' => [
                'description' => 'Parent phone.',
                'example' => '0907654321',
            ],
            'parents[].email' => [
                'description' => 'Parent email.',
                'example' => 'b@gmail.com',
            ],
            'parents[].relation' => [
                'description' => 'father|mother|guardian.',
                'example' => 'mother',
            ],
        ];
    }
}
