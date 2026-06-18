<?php

namespace App\Modules\Education\Material\Http\Requests;

use App\Modules\Education\Material\Enums\MaterialAccessType;
use App\Modules\Education\Material\Enums\MaterialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * material_code, current_version and status are not editable here (status changes
 * via publish; version via upload/rollback).
 */
class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_name' => ['sometimes', 'required', 'string', 'max:255'],
            'material_type' => ['sometimes', 'required', Rule::in(MaterialType::values())],
            'category_id' => ['nullable', 'integer', 'exists:edu_material_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'access_type' => ['sometimes', 'required', Rule::in(MaterialAccessType::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'material_name.required' => 'Tên tài liệu là bắt buộc.',
            'material_type.in' => 'Loại tài liệu không hợp lệ.',
            'access_type.in' => 'Đối tượng truy cập không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'material_name' => ['description' => 'Material name.', 'example' => 'Workbook Starter'],
            'material_type' => ['description' => 'Material type.', 'example' => 'pdf'],
            'category_id' => ['description' => 'Category id.', 'example' => 1],
            'description' => ['description' => 'Description.'],
            'access_type' => ['description' => 'Access audience: teacher, student, parent, internal.', 'example' => 'student'],
        ];
    }
}
