<?php

namespace App\Modules\Education\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['sometimes', 'required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Tên danh mục là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'category_name' => ['description' => 'Category name.', 'example' => 'Workbook'],
            'sort_order' => ['description' => 'Display order.', 'example' => 1],
            'status' => ['description' => 'active | inactive.', 'example' => 'active'],
        ];
    }
}
