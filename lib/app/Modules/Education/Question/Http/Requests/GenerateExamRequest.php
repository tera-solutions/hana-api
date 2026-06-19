<?php

namespace App\Modules\Education\Question\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamType;
use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Auto-generate an exam from the bank (spec §XI): the exam metadata plus the skill and
 * per-difficulty question counts to draw.
 */
class GenerateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_name' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', Rule::in(ExamType::values())],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'duration' => ['required', 'integer', 'gt:0'],
            'passing_score' => ['required', 'numeric', 'gte:0'],

            'skill' => ['required', Rule::in(QuestionSkill::values())],
            'difficulties' => ['required', 'array', 'min:1'],
            'difficulties.*.difficulty' => ['required', Rule::in(QuestionDifficulty::values())],
            'difficulties.*.count' => ['required', 'integer', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_name.required' => 'Tên đề thi là bắt buộc.',
            'skill.required' => 'Kỹ năng là bắt buộc.',
            'difficulties.required' => 'Cần ít nhất một mức độ khó.',
            'difficulties.*.count.gt' => 'Số lượng câu hỏi phải lớn hơn 0.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'exam_name' => ['description' => 'Generated exam name.', 'example' => 'Grammar Quiz - Starter'],
            'exam_type' => ['description' => 'Type: placement, progress, midterm, final, promotion, mock_test, certification.', 'example' => 'progress'],
            'course_id' => ['description' => 'Owning course (optional).', 'example' => 1],
            'level_id' => ['description' => 'Restrict to a level (optional).', 'example' => 1],
            'duration' => ['description' => 'Duration in minutes (> 0).', 'example' => 30],
            'passing_score' => ['description' => 'Minimum score to pass.', 'example' => 15],
            'skill' => ['description' => 'Skill to draw questions for.', 'example' => 'grammar'],
            'difficulties' => ['description' => 'Per-difficulty counts to draw.', 'example' => [['difficulty' => 'easy', 'count' => 10], ['difficulty' => 'medium', 'count' => 5]]],
        ];
    }
}
