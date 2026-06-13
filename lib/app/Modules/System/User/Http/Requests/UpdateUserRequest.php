<?php

namespace App\Modules\System\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ID and Username cannot be changed. Password is changed via reset-password.
 */
class UpdateUserRequest extends FormRequest
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
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/', Rule::unique('users', 'phone')->ignore($id)],

            'business_id' => ['sometimes', 'required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:sys_roles,id'],
            'department' => ['nullable', 'string', 'max:255'],

            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,locked'],
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
            'full_name' => [
                'description' => 'Full name.',
                'example' => 'Nguyen Van A',
            ],
            'email' => [
                'description' => 'Login email (unique).',
                'example' => 'a@hana.edu.vn',
            ],
            'phone' => [
                'description' => 'Phone (unique).',
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
            'role_id' => [
                'description' => 'Role id.',
                'example' => 1,
            ],
            'department' => [
                'description' => 'Department.',
                'example' => 'Academic',
            ],
            'gender' => [
                'description' => 'male|female|other.',
                'example' => 'male',
            ],
            'dob' => [
                'description' => 'Date of birth.',
                'example' => '1995-05-20',
            ],
            'avatar' => [
                'description' => 'Avatar path.',
                'example' => 'avatars/a.png',
            ],
            'status' => [
                'description' => 'active|inactive|locked.',
                'example' => 'active',
            ],
        ];
    }
}
