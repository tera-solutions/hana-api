<?php

namespace App\Modules\Education\Question\Http\Requests;

use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use App\Modules\Education\Question\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create input per spec §VI, §VIII. question_code, version and status are managed by the
 * service. Answer rules (BR001–BR003) are checked in withValidator against the type.
 */
class CreateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_type' => ['required', Rule::in(QuestionType::values())],
            'skill' => ['required', Rule::in(QuestionSkill::values())],
            'difficulty' => ['required', Rule::in(QuestionDifficulty::values())],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'category_id' => ['nullable', 'integer', 'exists:edu_question_categories,id'],
            'score' => ['required', 'numeric', 'gt:0'], // BR004
            'content' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('question_type');
            $answers = collect($this->input('answers', []));
            $correct = $answers->filter(fn ($a) => ! empty($a['is_correct']));

            if (in_array($type, QuestionType::answerBacked(), true)) {
                // BR001: at least one answer for answer-backed types.
                if ($answers->isEmpty()) {
                    $validator->errors()->add('answers', 'Câu hỏi phải có ít nhất một đáp án.');

                    return;
                }

                // BR002: single-correct types need exactly one correct answer.
                if (in_array($type, QuestionType::singleCorrect(), true) && $correct->count() !== 1) {
                    $validator->errors()->add('answers', 'Loại câu hỏi này phải có đúng một đáp án đúng.');
                }

                // BR003: multiple choice needs at least one correct answer.
                if (! in_array($type, QuestionType::singleCorrect(), true) && $correct->isEmpty()) {
                    $validator->errors()->add('answers', 'Phải có ít nhất một đáp án đúng.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'question_type.required' => 'Loại câu hỏi là bắt buộc.',
            'skill.required' => 'Kỹ năng là bắt buộc.',
            'difficulty.required' => 'Độ khó là bắt buộc.',
            'score.gt' => 'Điểm số phải lớn hơn 0.',
            'content.required' => 'Nội dung câu hỏi là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'question_type' => ['description' => 'Type: single_choice, multiple_choice, true_false, matching, ordering, fill_blank, short_answer, essay, speaking, listening.', 'example' => 'single_choice'],
            'skill' => ['description' => 'Skill: listening, speaking, reading, writing, grammar, vocabulary.', 'example' => 'grammar'],
            'difficulty' => ['description' => 'Difficulty: easy, medium, hard.', 'example' => 'easy'],
            'level_id' => ['description' => 'Owning level (optional).', 'example' => 1],
            'category_id' => ['description' => 'Owning category (optional).', 'example' => 1],
            'score' => ['description' => 'Score (> 0).', 'example' => 2],
            'content' => ['description' => 'Question content.', 'example' => 'What color is the sky?'],
            'explanation' => ['description' => 'Explanation (optional).', 'example' => 'The sky appears blue.'],
            'answers' => ['description' => 'Answer options.', 'example' => [['answer_key' => 'A', 'answer_content' => 'Red', 'is_correct' => false], ['answer_key' => 'B', 'answer_content' => 'Blue', 'is_correct' => true]]],
            'tag_ids' => ['description' => 'IDs of existing question tags to attach.', 'example' => [1, 2]],
        ];
    }
}
