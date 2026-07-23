<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Http\Requests;

use App\Modules\Education\Evaluation\Enums\EvaluationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEvaluationCriteriaTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Criteria must be one of EvaluationType::criteria() for the chosen type —
        // otherwise the template would produce a criterion key that
        // EvaluationService::assertCriteriaBelongToType() later rejects.
        $type = EvaluationType::tryFrom($this->input('evaluation_type'));
        $allowed = $type?->criteria() ?? [];

        return [
            'evaluation_type' => ['required', 'string', Rule::in(EvaluationType::values())],
            'name' => ['required', 'string', 'max:255'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*' => ['required', 'string', 'max:100', Rule::in($allowed)],
            // Shared (business-wide) templates are admin-only — enforced in
            // the service regardless of what's sent here.
            'is_shared' => ['nullable', 'boolean'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluation_type.required' => 'Vui lòng chọn loại đánh giá.',
            'name.required' => 'Vui lòng nhập tên bảng tiêu chí.',
            'criteria.required' => 'Cần ít nhất một tiêu chí.',
            'criteria.*.in' => 'Tiêu chí không hợp lệ cho loại đánh giá này.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'evaluation_type' => ['description' => 'teacher|student|parent.', 'example' => 'teacher'],
            'name' => ['description' => 'Tên bảng tiêu chí.', 'example' => 'Đánh giá giáo viên chuẩn'],
            'criteria' => ['description' => 'Danh sách tiêu chí.', 'example' => ['Chuyên môn', 'Phương pháp giảng dạy', 'Giao tiếp']],
            'is_shared' => ['description' => 'Dùng chung cho cả trung tâm (chỉ admin). Bỏ trống = riêng tư.', 'example' => true],
            'business_id' => ['description' => 'Business id.', 'example' => 1],
        ];
    }
}
