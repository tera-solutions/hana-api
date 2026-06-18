<?php

namespace App\Modules\Education\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'category_code' => ['required', 'string', 'max:255', 'unique:edu_material_categories,category_code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Tên danh mục là bắt buộc.',
            'category_code.required' => 'Mã danh mục là bắt buộc.',
            'category_code.unique' => 'Mã danh mục đã tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'category_name' => ['description' => 'Category name.', 'example' => 'Workbook'],
            'category_code' => ['description' => 'Unique category code.', 'example' => 'WORKBOOK'],
            'sort_order' => ['description' => 'Display order.', 'example' => 1],
            'status' => ['description' => 'active | inactive.', 'example' => 'active'],
        ];
    }
}
