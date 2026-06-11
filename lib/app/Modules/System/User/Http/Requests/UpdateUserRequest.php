<?php

namespace App\Modules\System\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ID and Username cannot be changed. Password is changed via reset-password.
 *
 * @bodyParam full_name string Full name. Example: Nguyen Van A
 * @bodyParam email string Login email (unique). Example: a@hana.edu.vn
 * @bodyParam phone string Phone (unique). Example: 0901234567
 * @bodyParam business_id integer Business id. Example: 1
 * @bodyParam branch_id integer Branch id. Example: 1
 * @bodyParam role_id integer Role id. Example: 1
 * @bodyParam department string Department. Example: Academic
 * @bodyParam gender string male|female|other. Example: male
 * @bodyParam dob date Date of birth. Example: 1995-05-20
 * @bodyParam avatar string Avatar path. Example: avatars/a.png
 * @bodyParam status string active|inactive|locked. Example: active
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
}
