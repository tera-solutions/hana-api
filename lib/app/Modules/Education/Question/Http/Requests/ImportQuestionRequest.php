<?php

namespace App\Modules\Education\Question\Http\Requests;

use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Import questions from an uploaded spreadsheet (spec §X). The file is referenced by its
 * uploaded media id; per-row parsing/validation and partial-success (BR008) live in the
 * service. Defaults apply to rows that omit a value.
 */
class ImportQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['required', 'integer', 'exists:media,id'],
            'file_name' => ['nullable', 'string', 'max:255'],

            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'category_id' => ['nullable', 'integer', 'exists:edu_question_categories,id'],
            'default_score' => ['nullable', 'numeric', 'gt:0'],
            'default_skill' => ['nullable', Rule::in(QuestionSkill::values())],
            'default_difficulty' => ['nullable', Rule::in(QuestionDifficulty::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'Tập tin import là bắt buộc.',
            'file_id.exists' => 'Tập tin import không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file_id' => ['description' => 'Uploaded media id of the spreadsheet (Question | Option A-D | Correct Answer | Difficulty | Skill).', 'example' => 10],
            'file_name' => ['description' => 'Original file name (optional, for display).', 'example' => 'questions.xlsx'],
            'level_id' => ['description' => 'Level applied to all imported questions (optional).', 'example' => 1],
            'category_id' => ['description' => 'Category applied to all imported questions (optional).', 'example' => 1],
            'default_score' => ['description' => 'Score for rows that omit one (default 1).', 'example' => 1],
            'default_skill' => ['description' => 'Skill fallback when a row omits it.', 'example' => 'grammar'],
            'default_difficulty' => ['description' => 'Difficulty fallback when a row omits it.', 'example' => 'easy'],
        ];
    }
}
