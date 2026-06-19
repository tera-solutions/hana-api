<?php

namespace App\Modules\Education\Question\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_code' => ['sometimes', 'string', 'max:100', Rule::unique('edu_question_categories', 'category_code')->ignore($this->route('id'))],
            'category_name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:edu_question_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_code.unique' => 'Mã danh mục đã tồn tại.',
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
