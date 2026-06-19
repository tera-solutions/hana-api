<?php

namespace App\Modules\Education\Exam\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamSkill;
use App\Modules\Education\Exam\Enums\QuestionDifficulty;
use App\Modules\Education\Exam\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill' => ['sometimes', Rule::in(ExamSkill::values())],
            'question_type' => ['sometimes', Rule::in(QuestionType::values())],
            'content' => ['sometimes', 'string'],
            'answer_key' => ['nullable', 'array'],
            'score' => ['sometimes', 'numeric', 'gte:0'],
            'difficulty' => ['sometimes', Rule::in(QuestionDifficulty::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'skill.in' => 'Kỹ năng không hợp lệ.',
            'question_type.in' => 'Loại câu hỏi không hợp lệ.',
            'difficulty.in' => 'Độ khó không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'skill' => ['description' => 'Skill: listening, speaking, reading, writing, grammar, vocabulary.', 'example' => 'reading'],
            'question_type' => ['description' => 'Type: single_choice, multiple_choice, fill_blank, matching, essay, speaking, listening.', 'example' => 'single_choice'],
            'content' => ['description' => 'Question content.', 'example' => 'What is the capital of France?'],
            'answer_key' => ['description' => 'Answer key (array; shape depends on type).', 'example' => ['A']],
            'score' => ['description' => 'Score awarded for this question.', 'example' => 2],
            'difficulty' => ['description' => 'Difficulty: easy, medium, hard.', 'example' => 'medium'],
        ];
    }
}
