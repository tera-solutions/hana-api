<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Process a teacher's resignation.
 */
class ResignTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resigned_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'resigned_at' => ['description' => 'Resignation date.', 'example' => '2026-06-30'],
            'reason' => ['description' => 'Reason for resignation.', 'example' => 'Chuyển công tác'],
        ];
    }
}
