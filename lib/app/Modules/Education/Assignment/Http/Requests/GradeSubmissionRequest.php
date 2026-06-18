<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'Điểm là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'score' => ['description' => 'Score (must not exceed the assignment max score).', 'example' => 9],
            'comment' => ['description' => 'Teacher comment.', 'example' => 'Làm tốt, cần chú ý ngữ pháp.'],
        ];
    }
}
