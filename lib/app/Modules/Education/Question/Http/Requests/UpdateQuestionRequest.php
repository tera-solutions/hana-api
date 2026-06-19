<?php

namespace App\Modules\Education\Question\Http\Requests;

use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use App\Modules\Education\Question\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_type' => ['sometimes', Rule::in(QuestionType::values())],
            'skill' => ['sometimes', Rule::in(QuestionSkill::values())],
            'difficulty' => ['sometimes', Rule::in(QuestionDifficulty::values())],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'category_id' => ['nullable', 'integer', 'exists:edu_question_categories,id'],
            'score' => ['sometimes', 'numeric', 'gt:0'], // BR004
            'content' => ['sometimes', 'string'],
            'explanation' => ['nullable', 'string'],
            'change_log' => ['nullable', 'string', 'max:1000'],

            'cefr_level' => ['nullable', 'string', 'max:50'],
            'cambridge_level' => ['nullable', 'string', 'max:50'],
            'learning_objective' => ['nullable', 'string', 'max:255'],
            'grammar_topic' => ['nullable', 'string', 'max:255'],
            'vocabulary_topic' => ['nullable', 'string', 'max:255'],

            'answers' => ['nullable', 'array'],
            'answers.*.answer_key' => ['nullable', 'string', 'max:50'],
            'answers.*.answer_content' => ['required_with:answers', 'string'],
            'answers.*.is_correct' => ['nullable', 'boolean'],
            'answers.*.sort_order' => ['nullable', 'integer'],

            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:edu_question_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.gt' => 'Điểm số phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'question_type' => ['description' => 'Type: single_choice, multiple_choice, true_false, matching, ordering, fill_blank, short_answer, essay, speaking, listening.', 'example' => 'single_choice'],
            'skill' => ['description' => 'Skill: listening, speaking, reading, writing, grammar, vocabulary.', 'example' => 'grammar'],
            'difficulty' => ['description' => 'Difficulty: easy, medium, hard.', 'example' => 'easy'],
            'score' => ['description' => 'Score (> 0).', 'example' => 2],
            'content' => ['description' => 'Question content.', 'example' => 'What color is the sky?'],
            'change_log' => ['description' => 'Change note recorded when versioning a used question.', 'example' => 'Fixed a typo.'],
            'answers' => ['description' => 'Replacement answer options.', 'example' => [['answer_key' => 'A', 'answer_content' => 'Red', 'is_correct' => false]]],
            'tag_ids' => ['description' => 'Replacement set of existing tag IDs to attach.', 'example' => [1, 2]],
        ];
    }
}
