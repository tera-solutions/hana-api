<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * student_id identifies the submitting student (no student-facing auth in this API).
 */
class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'answer' => ['nullable', 'string', 'max:20000'],
            'files' => ['nullable', 'array'],
            'files.*.file_id' => ['required', 'integer', 'min:1'],
            'files.*.file_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Học viên là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_id' => ['description' => 'Submitting student id.', 'example' => 1],
            'answer' => ['description' => 'Online text answer.', 'example' => 'My family has four people...'],
            'files' => ['description' => 'Uploaded files (array of {file_id, file_name}).', 'example' => [['file_id' => 10, 'file_name' => 'homework.pdf']]],
        ];
    }
}
