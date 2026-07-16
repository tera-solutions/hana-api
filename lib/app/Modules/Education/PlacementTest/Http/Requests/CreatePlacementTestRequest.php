<?php

namespace App\Modules\Education\PlacementTest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlacementTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cefr_level' => ['nullable', 'string', 'max:50'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'question_count' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên bài kiểm tra là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Test title.', 'example' => 'Kiểm tra đầu vào Tiếng Anh A1'],
            'cefr_level' => ['description' => 'Target CEFR level.', 'example' => 'A1'],
            'skills' => ['description' => 'Skills covered.', 'example' => ['listening', 'grammar']],
            'question_count' => ['description' => 'Number of questions.', 'example' => 50],
            'duration_minutes' => ['description' => 'Time limit in minutes.', 'example' => 60],
        ];
    }
}
