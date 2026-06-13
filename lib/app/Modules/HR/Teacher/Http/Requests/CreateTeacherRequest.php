<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create a teacher with their initial specialisations.
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

    public function bodyParameters(): array
    {
        return [
            'full_name' => ['description' => 'Full name.', 'example' => 'Jane Doe'],
            'code' => ['description' => 'Unique teacher code.', 'example' => 'TCH001'],
            'avatar' => ['description' => 'Avatar path.', 'example' => 'avatars/jane.png'],
            'gender' => ['description' => 'male|female|other.', 'example' => 'female'],
            'dob' => ['description' => 'Date of birth (<= today).', 'example' => '1990-05-12'],
            'email' => ['description' => 'Unique email.', 'example' => 'jane@hana.edu.vn'],
            'phone' => ['description' => 'Unique phone.', 'example' => '0901234567'],
            'identity_no' => ['description' => 'ID / passport number.', 'example' => '079012345678'],
            'address' => ['description' => 'Address.', 'example' => '123 Lê Lợi, Hà Nội'],
            'branch_id' => ['description' => 'Branch id.', 'example' => 1],
            'joined_at' => ['description' => 'Joining date.', 'example' => '2026-01-10'],
            'teacher_type' => ['description' => 'full_time|part_time|freelancer|assistant.', 'example' => 'full_time'],
            'employment_type' => ['description' => 'Cooperation form.', 'example' => 'contract'],
            'hourly_rate' => ['description' => 'Hourly rate.', 'example' => 150000],
            'monthly_salary' => ['description' => 'Monthly salary.', 'example' => 15000000],
            'manager_id' => ['description' => 'Manager user id.', 'example' => 1],
            'business_id' => ['description' => 'Business id.', 'example' => 1],
            'note' => ['description' => 'Note.', 'example' => 'Giáo viên tốt nghiệp đại học Ngoại ngữ'],
            'skills' => ['description' => 'Specialisations (at least one required).', 'example' => []],
            'skills[].skill_name' => ['description' => 'Skill name.', 'example' => 'IELTS'],
            'skills[].level' => ['description' => 'Skill level.', 'example' => 'expert'],
        ];
    }
}
