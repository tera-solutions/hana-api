<?php

namespace App\Modules\Education\Material\Http\Requests;

use App\Modules\Education\Material\Enums\MaterialEntityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', Rule::in(MaterialEntityType::values())],
            'entity_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.required' => 'Loại đối tượng là bắt buộc.',
            'entity_type.in' => 'Loại đối tượng không hợp lệ.',
            'entity_id.required' => 'Đối tượng liên kết là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'entity_type' => ['description' => 'Linked entity: course, lesson_plan, lesson, assignment, evaluation, exam.', 'example' => 'lesson'],
            'entity_id' => ['description' => 'Id of the linked entity.', 'example' => 1],
        ];
    }
}
