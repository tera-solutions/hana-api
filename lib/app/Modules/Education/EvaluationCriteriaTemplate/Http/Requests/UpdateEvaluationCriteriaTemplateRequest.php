<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Http\Requests;

use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\EvaluationCriteriaTemplate\Models\EvaluationCriteriaTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationCriteriaTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // The template's evaluation_type can't be changed here, so criteria
        // are validated against the EXISTING record's type (evaluation_type
        // isn't in the payload) — same allowlist EvaluationService enforces.
        $template = EvaluationCriteriaTemplate::find($this->route('id'));
        $type = $template ? EvaluationType::tryFrom($template->evaluation_type) : null;
        $allowed = $type?->criteria() ?? [];

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'criteria' => ['sometimes', 'required', 'array', 'min:1'],
            'criteria.*' => ['required', 'string', 'max:100', Rule::in($allowed)],
            'criteria_descriptions' => ['nullable', 'array'],
            'criteria_descriptions.*' => ['array'],
            'criteria_descriptions.*.*.level' => ['required', 'integer', 'min:1', 'max:5'],
            'criteria_descriptions.*.*.label' => ['required', 'string', 'max:255'],
            'is_shared' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'criteria.*.in' => 'Tiêu chí không hợp lệ cho loại đánh giá này.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên bảng tiêu chí.', 'example' => 'Đánh giá giáo viên chuẩn'],
            'criteria' => ['description' => 'Danh sách tiêu chí.', 'example' => ['Chuyên môn', 'Phương pháp giảng dạy']],
            'criteria_descriptions' => ['description' => 'Mô tả từng mức điểm (1-5) theo tiêu chí.', 'example' => ['expertise' => [['level' => 1, 'label' => 'Yếu'], ['level' => 5, 'label' => 'Xuất sắc']]]],
            'is_shared' => ['description' => 'Dùng chung cho cả trung tâm (chỉ admin).', 'example' => true],
        ];
    }
}
