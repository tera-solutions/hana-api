<?php

namespace App\Modules\Education\PlacementTest\Http\Requests;

use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Draw bank questions into a placement test's question set (spec: shared
 * question bank feeding both Exam and Placement Test). One test can span
 * several skills, so — unlike `GenerateExamRequest` — skill is per-bucket.
 */
class GeneratePlacementTestQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buckets' => ['required', 'array', 'min:1'],
            'buckets.*.skill' => ['required', Rule::in(QuestionSkill::values())],
            'buckets.*.difficulty' => ['required', Rule::in(QuestionDifficulty::values())],
            'buckets.*.count' => ['required', 'integer', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'buckets.required' => 'Cần ít nhất một mức độ khó.',
            'buckets.*.count.gt' => 'Số lượng câu hỏi phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'buckets' => [
                'description' => 'Per skill+difficulty counts to draw.',
                'example' => [
                    ['skill' => 'grammar', 'difficulty' => 'easy', 'count' => 5],
                    ['skill' => 'vocabulary', 'difficulty' => 'medium', 'count' => 5],
                ],
            ],
        ];
    }
}
