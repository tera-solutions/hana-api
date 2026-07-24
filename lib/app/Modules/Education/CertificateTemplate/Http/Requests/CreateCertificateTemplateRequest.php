<?php

namespace App\Modules\Education\CertificateTemplate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCertificateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'preview_image' => ['nullable', 'string', 'max:2048'],
            'placeholders' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên mẫu chứng nhận.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên mẫu chứng nhận.', 'example' => 'Mẫu A'],
            'preview_image' => ['description' => 'Đường dẫn/URL ảnh xem trước.', 'example' => 'https://cdn.example.com/certs/template-a.png'],
            'placeholders' => ['description' => 'Danh sách placeholder trên mẫu.', 'example' => ['student_name', 'course_name', 'issued_at', 'signature']],
            'status' => ['description' => 'Status: active or inactive (default active).', 'example' => 'active'],
        ];
    }
}
