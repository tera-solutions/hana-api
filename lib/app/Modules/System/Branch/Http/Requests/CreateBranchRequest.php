<?php

namespace App\Modules\System\Branch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    public function bodyParameters(): array
    {
        return [
            'business_id' => [
                'description' => 'Owning business id (must be active).',
                'example' => 1,
            ],
            'code' => [
                'description' => 'Branch code, unique within the business.',
                'example' => 'Q1',
            ],
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
            'opened_at' => [
                'description' => 'Opening date.',
                'example' => '2026-01-01',
            ],
        ];
    }
}
