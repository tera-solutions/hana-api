<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'override' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.required' => 'Ngày bắt đầu là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'from_date' => ['description' => 'Generate lessons starting from this date (Y-m-d).', 'example' => '2026-07-01'],
            'override' => ['description' => 'Delete existing non-completed/locked lessons before generating.', 'example' => false],
        ];
    }
}
