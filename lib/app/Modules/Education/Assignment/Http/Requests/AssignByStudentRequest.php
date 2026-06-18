<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignByStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct', 'exists:edu_students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Danh sách học viên là bắt buộc.',
            'student_ids.min' => 'Cần chọn ít nhất một học viên.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_ids' => ['description' => 'Student ids to assign to.', 'example' => [1, 2, 3]],
        ];
    }
}
