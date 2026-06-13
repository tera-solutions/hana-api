<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Teacher code is immutable; status changes via suspend/restore/resign.
 *
 * @bodyParam full_name string Full name. Example: Jane Doe
 * @bodyParam avatar string Avatar URL.
 * @bodyParam gender string male|female|other. Example: female
 * @bodyParam dob date Date of birth. Example: 1990-05-12
 * @bodyParam email string Unique email. Example: jane@hana.edu.vn
 * @bodyParam phone string Unique phone. Example: 0901234567
 * @bodyParam identity_no string ID / passport number.
 * @bodyParam address string Address.
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam teacher_type string full_time|part_time|freelancer|assistant. Example: part_time
 * @bodyParam employment_type string Cooperation form. Example: contract
 * @bodyParam hourly_rate number Hourly rate. Example: 150000
 * @bodyParam monthly_salary number Monthly salary. Example: 15000000
 * @bodyParam manager_id integer Manager user id. Example: 1
 * @bodyParam note string Note.
 * @bodyParam skills object[] Specializations (replaces existing).
 * @bodyParam skills[].skill_name string required Skill name. Example: TOEIC
 * @bodyParam skills[].level string Skill level. Example: intermediate
 */
class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('hr_teachers', 'email')->ignore($id)],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/', Rule::unique('hr_teachers', 'phone')->ignore($id)],
            'identity_no' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],

            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'teacher_type' => ['sometimes', 'required', 'string', 'in:full_time,part_time,freelancer,assistant'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],

            'skills' => ['nullable', 'array'],
            'skills.*.skill_name' => ['required', 'string', 'max:255'],
            'skills.*.level' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email đã tồn tại.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'code' => [
                'description' => 'Unique teacher code.',
                'example' => 'T0001',
            ],
            'name' => [
                'description' => 'Teacher full name.',
                'example' => 'Jane Doe',
            ],
            'user_id' => [
                'description' => 'Linked user id.',
                'example' => 1,
            ],
            'business_id' => [
                'description' => 'Owning business id.',
                'example' => 1,
            ],
            'type' => [
                'description' => 'Teacher type.',
                'example' => 'teacher',
            ],
            'status' => [
                'description' => 'active|inactive.',
                'example' => 'active',
            ],
            'salary_per_hour' => [
                'description' => 'Hourly salary.',
                'example' => 150000,
            ],
        ];
    }
}
