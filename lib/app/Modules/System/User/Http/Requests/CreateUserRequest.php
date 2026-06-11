<?php

namespace App\Modules\System\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * @bodyParam full_name string required Full name. Example: Nguyen Van A
 * @bodyParam email string required Login email (unique). Example: a@hana.edu.vn
 * @bodyParam phone string required Phone (unique). Example: 0901234567
 * @bodyParam username string required Login username (unique). Example: teacher01
 * @bodyParam password string required Min 8, upper/lower/number. Example: Abc@1234
 * @bodyParam password_confirmation string required Must match password. Example: Abc@1234
 * @bodyParam business_id integer required Business id. Example: 1
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam role_id integer required Role id. Example: 1
 * @bodyParam department string Department. Example: Academic
 * @bodyParam gender string male|female|other. Example: male
 * @bodyParam dob date Date of birth. Example: 1995-05-20
 * @bodyParam avatar string Avatar path. Example: avatars/a.png
 * @bodyParam status string required active|inactive|locked. Example: active
 */
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
}
