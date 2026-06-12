<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [],
        ];
    }
}
