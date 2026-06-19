<?php

namespace App\Modules\Education\Question\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuestionTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tag_name' => ['required', 'string', 'max:100', 'unique:edu_question_tags,tag_name'],
        ];
    }

    public function messages(): array
    {
        return [
            'tag_name.required' => 'Tên tag là bắt buộc.',
            'tag_name.unique' => 'Tag đã tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'tag_name' => ['description' => 'Unique tag name.', 'example' => 'colors'],
        ];
    }
}
