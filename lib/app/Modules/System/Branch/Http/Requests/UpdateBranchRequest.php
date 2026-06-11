<?php

namespace App\Modules\System\Branch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ID, Code and Business cannot be changed once created.
 *
 * @bodyParam name string Branch name. Example: Chi nhánh Quận 1
 * @bodyParam short_name string Short name. Example: Q1
 * @bodyParam status string active|inactive. Example: active
 * @bodyParam phone string Hotline. Example: 0901234567
 * @bodyParam email string Contact email. Example: q1@hana.edu.vn
 * @bodyParam website string Website. Example: https://hana.edu.vn
 * @bodyParam address string Address. Example: 123 Le Loi
 * @bodyParam province string Province. Example: Ho Chi Minh
 * @bodyParam district string District. Example: District 1
 * @bodyParam ward string Ward. Example: Ben Nghe
 * @bodyParam postal_code string Postal code. Example: 700000
 * @bodyParam manager_id integer Branch manager user id. Example: 1
 * @bodyParam capacity integer Max capacity. Example: 200
 */
class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,suspended'],

            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('sys_branches', 'email')->ignore($id)],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:1000'],
            'province' => ['sometimes', 'required', 'string', 'max:255'],
            'district' => ['sometimes', 'required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email đã tồn tại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ];
    }
}
