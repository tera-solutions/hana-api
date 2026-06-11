<?php

namespace App\Modules\System\Business\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam business_code string required Unique business code. Example: HCM001
 * @bodyParam name string required Business name. Example: Hana English HCM
 * @bodyParam short_name string Short name. Example: Hana HCM
 * @bodyParam prefix string required Uppercase code prefix. Example: HCM
 * @bodyParam tax_code string Tax code. Example: 0312345678
 * @bodyParam website string Website. Example: https://hana.edu.vn
 * @bodyParam phone string required Hotline. Example: 0901234567
 * @bodyParam email string required Contact email. Example: hcm@hana.edu.vn
 * @bodyParam address string required Address. Example: 123 Le Loi
 * @bodyParam province string required Province. Example: Ho Chi Minh
 * @bodyParam district string required District. Example: District 1
 * @bodyParam ward string Ward. Example: Ben Nghe
 * @bodyParam zip_code string Zip code. Example: 700000
 * @bodyParam manager_id integer Manager user id. Example: 1
 * @bodyParam status string required active|inactive. Example: active
 */
class CreateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_code' => ['required', 'string', 'max:255', 'unique:sys_business,business_code'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'prefix' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9]+$/'],
            'tax_code' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],

            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:sys_business,email'],
            'address' => ['required', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],

            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_code.unique' => 'Business Code đã tồn tại.',
            'email.unique' => 'Email đã tồn tại.',
            'prefix.regex' => 'Prefix chỉ gồm chữ hoa và số.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ];
    }
}
