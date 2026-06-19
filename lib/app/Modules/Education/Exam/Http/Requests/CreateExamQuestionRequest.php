<?php

namespace App\Modules\Education\Exam\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamSkill;
use App\Modules\Education\Exam\Enums\QuestionDifficulty;
use App\Modules\Education\Exam\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Exam-owned question input (spec §VII).
 */
class CreateExamQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill' => ['required', Rule::in(ExamSkill::values())],
            'question_type' => ['required', Rule::in(QuestionType::values())],
            'content' => ['required', 'string'],
            'answer_key' => ['nullable', 'array'],
            'score' => ['required', 'numeric', 'gte:0'],
            'difficulty' => ['nullable', Rule::in(QuestionDifficulty::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'skill.required' => 'Kỹ năng là bắt buộc.',
            'skill.in' => 'Kỹ năng không hợp lệ.',
            'question_type.required' => 'Loại câu hỏi là bắt buộc.',
            'question_type.in' => 'Loại câu hỏi không hợp lệ.',
            'content.required' => 'Nội dung câu hỏi là bắt buộc.',
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
