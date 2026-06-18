<?php

namespace App\Modules\Education\Material\Http\Requests;

use App\Modules\Education\Material\Enums\MaterialAccessType;
use App\Modules\Education\Material\Enums\MaterialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_name' => ['required', 'string', 'max:255'],
            'material_type' => ['required', Rule::in(MaterialType::values())],
            'category_id' => ['nullable', 'integer', 'exists:edu_material_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'access_type' => ['required', Rule::in(MaterialAccessType::values())],

            // Optional first version uploaded together with the material (material.md §7).
            'file_id' => ['nullable', 'integer', 'min:1'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'change_log' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'material_name.required' => 'Tên tài liệu là bắt buộc.',
            'material_type.required' => 'Loại tài liệu là bắt buộc.',
            'material_type.in' => 'Loại tài liệu không hợp lệ.',
            'access_type.required' => 'Đối tượng truy cập là bắt buộc.',
            'access_type.in' => 'Đối tượng truy cập không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'material_name' => ['description' => 'Material name.', 'example' => 'Workbook Starter'],
            'material_type' => ['description' => 'Type: pdf, document, image, video, audio, presentation, worksheet, homework, exam, other.', 'example' => 'pdf'],
            'category_id' => ['description' => 'Category id.', 'example' => 1],
            'description' => ['description' => 'Description.'],
            'access_type' => ['description' => 'Access audience: teacher, student, parent, internal.', 'example' => 'student'],
            'file_id' => ['description' => 'Reference to the uploaded file (optional first version).', 'example' => 10],
            'file_name' => ['description' => 'Original file name.', 'example' => 'workbook-starter.pdf'],
            'file_size' => ['description' => 'File size in bytes.', 'example' => 1048576],
            'mime_type' => ['description' => 'File mime type.', 'example' => 'application/pdf'],
            'change_log' => ['description' => 'Change note for this version.', 'example' => 'Bản phát hành đầu tiên'],
        ];
    }
}
