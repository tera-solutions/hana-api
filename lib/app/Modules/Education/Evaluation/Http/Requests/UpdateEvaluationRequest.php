<?php

namespace App\Modules\Education\Evaluation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edits the descriptive fields and (optionally) the criteria of an evaluation. The
 * type/target/evaluator and context are fixed at creation; the total score is
 * re-computed when criteria change (BR-03). Locked evaluations are rejected in the
 * service (BR-02).
 */
class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'criteria' => ['sometimes', 'array', 'min:1'],
            'criteria.*.criterion' => ['required_with:criteria', 'string', 'max:100'],
            'criteria.*.score' => ['required_with:criteria', 'numeric', 'between:1,5'],

            'comment' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],

            'evaluated_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'criteria.min' => 'Cần ít nhất một tiêu chí đánh giá.',
            'criteria.*.score.between' => 'Điểm tiêu chí phải từ 1 đến 5.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'criteria' => ['description' => 'Danh sách tiêu chí và điểm (1-5). Điểm tổng được tính lại tự động.', 'example' => [['criterion' => 'knowledge', 'score' => 4]]],
            'comment' => ['description' => 'Nhận xét chung.', 'example' => 'Đã cải thiện.'],
            'strengths' => ['description' => 'Điểm mạnh.', 'example' => 'Tự tin hơn.'],
            'weaknesses' => ['description' => 'Điểm cần cải thiện.', 'example' => 'Từ vựng.'],
            'recommendations' => ['description' => 'Khuyến nghị.', 'example' => 'Đọc thêm sách.'],
            'evaluated_at' => ['description' => 'Ngày đánh giá.', 'example' => '2026-06-24'],
        ];
    }
}
