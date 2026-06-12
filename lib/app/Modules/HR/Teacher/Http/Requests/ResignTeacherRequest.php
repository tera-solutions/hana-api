<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam resigned_at date required Resignation date. Example: 2026-06-30
 * @bodyParam reason string required Reason for resignation. Example: Chuyển công tác
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
}
