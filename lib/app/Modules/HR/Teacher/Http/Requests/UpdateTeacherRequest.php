<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a teacher. Code and status are immutable.
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
            'bank_account' => ['nullable', 'array'],
            'bank_account.bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account.bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_account.bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account.bank_branch' => ['nullable', 'string', 'max:255'],
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
            'full_name' => ['description' => 'Full name.', 'example' => 'Jane Doe'],
            'avatar' => ['description' => 'Avatar path.', 'example' => 'avatars/jane.png'],
            'gender' => ['description' => 'male|female|other.', 'example' => 'female'],
            'dob' => ['description' => 'Date of birth (<= today).', 'example' => '1990-05-12'],
            'email' => ['description' => 'Unique email.', 'example' => 'jane@hana.edu.vn'],
            'phone' => ['description' => 'Unique phone.', 'example' => '0901234567'],
            'identity_no' => ['description' => 'ID / passport number.', 'example' => '079012345678'],
            'address' => ['description' => 'Address.', 'example' => '123 Lê Lợi, Hà Nội'],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'teacher_type' => ['description' => 'full_time|part_time|freelancer|assistant.', 'example' => 'part_time'],
            'employment_type' => ['description' => 'Cooperation form.', 'example' => 'contract'],
            'hourly_rate' => ['description' => 'Hourly rate.', 'example' => 200000],
            'monthly_salary' => ['description' => 'Monthly salary.', 'example' => 15000000],
            'bank_account' => ['description' => 'Bank account (Tài khoản ngân hàng).'],
            'bank_account.bank_name' => ['description' => 'Bank name.', 'example' => 'Vietcombank'],
            'bank_account.bank_account_number' => ['description' => 'Account number.', 'example' => '0123456789'],
            'bank_account.bank_account_holder' => ['description' => 'Account holder name.', 'example' => 'NGUYEN VAN A'],
            'bank_account.bank_branch' => ['description' => 'Branch (optional).', 'example' => 'Chi nhánh Hà Nội'],
            'manager_id' => ['description' => 'Manager user id.', 'example' => 1],
            'note' => ['description' => 'Note.', 'example' => 'Chuyển sang dạy part-time'],
            'skills' => ['description' => 'Specialisations (replaces all existing when provided).', 'example' => []],
            'skills[].skill_name' => ['description' => 'Skill name.', 'example' => 'TOEIC'],
            'skills[].level' => ['description' => 'Skill level.', 'example' => 'intermediate'],
        ];
    }
}
