<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required
 */
class ExportStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
