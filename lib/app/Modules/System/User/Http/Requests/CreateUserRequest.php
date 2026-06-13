<?php

namespace App\Modules\System\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/', 'unique:users,phone'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],

            'business_id' => ['required', 'integer', 'exists:sys_business,id'],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'role_id' => ['required', 'integer', 'exists:sys_roles,id'],
            'department' => ['nullable', 'string', 'max:255'],

            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:active,inactive,locked'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email đã tồn tại.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
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
            'username' => [
                'description' => 'Login username (unique).',
                'example' => 'teacher01',
            ],
            'password' => [
                'description' => 'Min 8, upper/lower/number.',
                'example' => 'Abc@1234',
            ],
            'password_confirmation' => [
                'description' => 'Must match password.',
                'example' => 'Abc@1234',
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
