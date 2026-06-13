<?php

namespace App\Modules\System\Business\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Business Code and ID cannot be changed once created.
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

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Business name.',
                'example' => 'Hana English HCM',
            ],
            'short_name' => [
                'description' => 'Short name.',
                'example' => 'Hana HCM',
            ],
            'prefix' => [
                'description' => 'Uppercase code prefix.',
                'example' => 'HCM',
            ],
            'tax_code' => [
                'description' => 'Tax code.',
                'example' => '0312345678',
            ],
            'website' => [
                'description' => 'Website.',
                'example' => 'https://hana.edu.vn',
            ],
            'phone' => [
                'description' => 'Hotline.',
                'example' => '0901234567',
            ],
            'email' => [
                'description' => 'Contact email.',
                'example' => 'hcm@hana.edu.vn',
            ],
            'address' => [
                'description' => 'Address.',
                'example' => '123 Le Loi',
            ],
            'province' => [
                'description' => 'Province.',
                'example' => 'Ho Chi Minh',
            ],
            'district' => [
                'description' => 'District.',
                'example' => 'District 1',
            ],
            'ward' => [
                'description' => 'Ward.',
                'example' => 'Ben Nghe',
            ],
            'zip_code' => [
                'description' => 'Zip code.',
                'example' => '700000',
            ],
            'manager_id' => [
                'description' => 'Manager user id.',
                'example' => 1,
            ],
            'status' => [
                'description' => 'active|inactive.',
                'example' => 'active',
            ],
        ];
    }
}
