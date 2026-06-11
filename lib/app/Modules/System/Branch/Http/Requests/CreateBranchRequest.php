<?php

namespace App\Modules\System\Branch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam business_id integer required Owning business id (must be active). Example: 1
 * @bodyParam code string required Branch code, unique within the business. Example: Q1
 * @bodyParam name string required Branch name. Example: Chi nhánh Quận 1
 * @bodyParam short_name string Short name. Example: Q1
 * @bodyParam status string required active|inactive. Example: active
 * @bodyParam phone string required Hotline. Example: 0901234567
 * @bodyParam email string required Contact email. Example: q1@hana.edu.vn
 * @bodyParam website string Website. Example: https://hana.edu.vn
 * @bodyParam address string required Address. Example: 123 Le Loi
 * @bodyParam province string required Province. Example: Ho Chi Minh
 * @bodyParam district string required District. Example: District 1
 * @bodyParam ward string Ward. Example: Ben Nghe
 * @bodyParam postal_code string Postal code. Example: 700000
 * @bodyParam manager_id integer Branch manager user id. Example: 1
 * @bodyParam capacity integer Max capacity. Example: 200
 * @bodyParam opened_at date Opening date. Example: 2026-01-01
 */
class CreateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', Rule::exists('sys_business', 'id')->where('status', 'active')],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('sys_branches', 'code')->where('business_id', $this->input('business_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],

            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s().]{6,20}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:sys_branches,email'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'opened_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'email.unique' => 'Email đã tồn tại.',
            'business_id.exists' => 'Business không tồn tại hoặc không hoạt động.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
        ];
    }
}
