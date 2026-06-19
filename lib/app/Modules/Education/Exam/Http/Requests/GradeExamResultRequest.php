<?php

namespace App\Modules\Education\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Per-skill grading input (spec §XI). At least one skill score is required; the total
 * cap (BR006) is enforced by the service against the exam's total_score.
 */
class GradeExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listening_score' => ['nullable', 'numeric', 'gte:0'],
            'speaking_score' => ['nullable', 'numeric', 'gte:0'],
            'reading_score' => ['nullable', 'numeric', 'gte:0'],
            'writing_score' => ['nullable', 'numeric', 'gte:0'],
            'grammar_score' => ['nullable', 'numeric', 'gte:0'],
            'vocabulary_score' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $skills = ['listening_score', 'speaking_score', 'reading_score', 'writing_score', 'grammar_score', 'vocabulary_score'];

            if (! collect($skills)->contains(fn ($s) => $this->filled($s))) {
                $validator->errors()->add('listening_score', 'Cần nhập ít nhất một điểm kỹ năng.');
            }
        });
    }

    public function bodyParameters(): array
    {
        return [
            'listening_score' => ['description' => 'Listening score.', 'example' => 20],
            'speaking_score' => ['description' => 'Speaking score.', 'example' => 15],
            'reading_score' => ['description' => 'Reading score.', 'example' => 20],
            'writing_score' => ['description' => 'Writing score.', 'example' => 15],
            'grammar_score' => ['description' => 'Grammar score.', 'example' => 15],
            'vocabulary_score' => ['description' => 'Vocabulary score.', 'example' => 15],
        ];
    }
}
