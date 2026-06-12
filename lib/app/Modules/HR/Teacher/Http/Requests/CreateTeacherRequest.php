<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam full_name string required Full name. Example: Jane Doe
 * @bodyParam code string required Unique teacher code. Example: T0001
 * @bodyParam avatar string Avatar URL.
 * @bodyParam gender string male|female|other. Example: female
 * @bodyParam dob date Date of birth. Example: 1990-05-12
 * @bodyParam email string required Unique email. Example: jane@hana.edu.vn
 * @bodyParam phone string required Unique phone. Example: 0901234567
 * @bodyParam identity_no string ID / passport number. Example: 0790xxxxxxx
 * @bodyParam address string Address.
 * @bodyParam branch_id integer required Branch id. Example: 1
 * @bodyParam joined_at date required Joining date. Example: 2026-01-10
 * @bodyParam teacher_type string required full_time|part_time|freelancer|assistant. Example: full_time
 * @bodyParam employment_type string required Cooperation form. Example: contract
 * @bodyParam hourly_rate number Hourly rate. Example: 150000
 * @bodyParam monthly_salary number Monthly salary. Example: 15000000
 * @bodyParam manager_id integer Manager user id. Example: 1
 * @bodyParam business_id integer Business id. Example: 1
 * @bodyParam note string Note.
 * @bodyParam skills object[] required Specializations.
 * @bodyParam skills[].skill_name string required Skill name. Example: IELTS
 * @bodyParam skills[].level string Skill level. Example: expert
 */
class CreateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:hr_teachers,code'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'email' => ['required', 'email', 'max:255', 'unique:hr_teachers,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/', 'unique:hr_teachers,phone'],
            'identity_no' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],

            'branch_id' => ['required', 'integer', 'exists:sys_branches,id'],
            'joined_at' => ['required', 'date'],

            'teacher_type' => ['required', 'string', 'in:full_time,part_time,freelancer,assistant'],
            'employment_type' => ['required', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],
            'note' => ['nullable', 'string', 'max:2000'],

            'skills' => ['required', 'array', 'min:1'],
            'skills.*.skill_name' => ['required', 'string', 'max:255'],
            'skills.*.level' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã giáo viên đã tồn tại.',
            'email.unique' => 'Email đã tồn tại.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'skills.required' => 'Chuyên môn là bắt buộc.',
        ];
    }
}
