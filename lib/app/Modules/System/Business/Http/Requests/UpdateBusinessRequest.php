<?php

namespace App\Modules\System\Business\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Business Code and ID cannot be changed once created.
 *
 * @bodyParam name string Business name. Example: Hana English HCM
 * @bodyParam short_name string Short name. Example: Hana HCM
 * @bodyParam prefix string Uppercase code prefix. Example: HCM
 * @bodyParam tax_code string Tax code. Example: 0312345678
 * @bodyParam website string Website. Example: https://hana.edu.vn
 * @bodyParam phone string Hotline. Example: 0901234567
 * @bodyParam email string Contact email. Example: hcm@hana.edu.vn
 * @bodyParam address string Address. Example: 123 Le Loi
 * @bodyParam province string Province. Example: Ho Chi Minh
 * @bodyParam district string District. Example: District 1
 * @bodyParam ward string Ward. Example: Ben Nghe
 * @bodyParam zip_code string Zip code. Example: 700000
 * @bodyParam manager_id integer Manager user id. Example: 1
 * @bodyParam status string active|inactive. Example: active
 */
class UpdateBusinessRequest extends FormRequest
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
            'prefix' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[A-Z0-9]+$/'],
            'tax_code' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],

            'phone' => ['sometimes', 'required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('sys_business', 'email')->ignore($id)],
            'address' => ['sometimes', 'required', 'string', 'max:1000'],
            'province' => ['sometimes', 'required', 'string', 'max:255'],
            'district' => ['sometimes', 'required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],

            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,suspended'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email đã tồn tại.',
            'prefix.regex' => 'Prefix chỉ gồm chữ hoa và số.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ];
    }
}
