<?php

namespace App\Modules\Education\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sales_id.exists' => 'Nhân viên tư vấn không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'sales_id' => ['description' => 'ID nhân viên tư vấn.', 'example' => 5],
            'note' => ['description' => 'Ghi chú.'],
        ];
    }
}
