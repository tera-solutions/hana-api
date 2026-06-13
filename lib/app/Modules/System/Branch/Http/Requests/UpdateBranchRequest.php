<?php

namespace App\Modules\System\Branch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ID, Code and Business cannot be changed once created.
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

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Branch name.',
                'example' => 'Chi nhánh Quận 1',
            ],
            'short_name' => [
                'description' => 'Short name.',
                'example' => 'Q1',
            ],
            'status' => [
                'description' => 'active|inactive.',
                'example' => 'active',
            ],
            'phone' => [
                'description' => 'Hotline.',
                'example' => '0901234567',
            ],
            'email' => [
                'description' => 'Contact email.',
                'example' => 'q1@hana.edu.vn',
            ],
            'website' => [
                'description' => 'Website.',
                'example' => 'https://hana.edu.vn',
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
            'postal_code' => [
                'description' => 'Postal code.',
                'example' => '700000',
            ],
            'manager_id' => [
                'description' => 'Branch manager user id.',
                'example' => 1,
            ],
            'capacity' => [
                'description' => 'Max capacity.',
                'example' => 200,
            ],
        ];
    }
}
