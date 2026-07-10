<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'class_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'branch_id' => ['nullable', 'integer'],
            'level_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_id' => [
                'description' => 'Filter to students enrolled in a given class (roster export).',
                'example' => 1,
            ],
            'search' => [
                'description' => 'Search by code, name, email, phone or parent name.',
            ],
        ];
    }
}
