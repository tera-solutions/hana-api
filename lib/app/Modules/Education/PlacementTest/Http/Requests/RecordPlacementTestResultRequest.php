<?php

namespace App\Modules\Education\PlacementTest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPlacementTestResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'cefr_result' => ['nullable', 'string', 'max:50'],
            'completion_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['in_progress', 'completed'])],
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
            'student_id' => ['description' => 'Student taking the test.', 'example' => 1],
            'score' => ['description' => 'Score achieved.', 'example' => 7.5],
            'cefr_result' => ['description' => 'Resulting CEFR level.', 'example' => 'A2'],
            'completion_rate' => ['description' => 'Completion percentage (0-100).', 'example' => 100],
            'status' => ['description' => 'Result status: in_progress or completed.', 'example' => 'completed'],
        ];
    }
}
