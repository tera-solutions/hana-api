<?php

namespace App\Modules\Education\Score\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveScoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'type' => ['required', 'string', 'max:50'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
