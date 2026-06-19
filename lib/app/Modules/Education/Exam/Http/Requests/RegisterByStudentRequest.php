<?php

namespace App\Modules\Education\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterByStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:edu_students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Danh sách học viên là bắt buộc.',
            'student_ids.min' => 'Cần ít nhất một học viên.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_ids' => ['description' => 'Students to register for the sitting.', 'example' => [1, 2]],
        ];
    }
}
