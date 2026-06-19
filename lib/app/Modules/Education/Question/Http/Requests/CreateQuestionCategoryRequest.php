<?php

namespace App\Modules\Education\Question\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_code' => ['required', 'string', 'max:100', 'unique:edu_question_categories,category_code'],
            'category_name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:edu_question_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_code.required' => 'Mã danh mục là bắt buộc.',
            'category_code.unique' => 'Mã danh mục đã tồn tại.',
            'category_name.required' => 'Tên danh mục là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'category_code' => ['description' => 'Unique category code.', 'example' => 'GRAMMAR'],
            'category_name' => ['description' => 'Category name.', 'example' => 'Grammar'],
            'parent_id' => ['description' => 'Parent category (optional).', 'example' => 1],
        ];
    }
}
