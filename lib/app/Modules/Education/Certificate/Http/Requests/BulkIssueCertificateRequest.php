<?php

namespace App\Modules\Education\Certificate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkIssueCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:edu_students,id'],
            'template_id' => ['required', 'integer', 'exists:edu_certificate_templates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Vui lòng chọn khóa học',
            'student_ids.required' => 'Vui lòng chọn ít nhất 1 học viên',
            'student_ids.min' => 'Vui lòng chọn ít nhất 1 học viên',
            'template_id.required' => 'Vui lòng chọn mẫu chứng nhận',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'course_id' => ['description' => 'Khóa học.', 'example' => 1],
            'student_ids' => ['description' => 'Danh sách học viên đủ điều kiện.', 'example' => [40, 41]],
            'template_id' => ['description' => 'Mẫu chứng nhận.', 'example' => 1],
        ];
    }
}
