<?php

namespace App\Modules\Education\LessonPlanMaterial\Http\Requests;

use App\Modules\Education\LessonPlanMaterial\Enums\MaterialType;
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
            'file_id' => ['required', 'integer', 'min:1'],
            'material_type' => ['required', Rule::in(MaterialType::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'File là bắt buộc.',
            'material_type.in' => 'Loại tài liệu không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file_id' => ['description' => 'Reference to the uploaded file.', 'example' => 10],
            'material_type' => ['description' => 'Material type: pdf, video, audio, slide, worksheet, homework.', 'example' => 'pdf'],
        ];
    }
}
