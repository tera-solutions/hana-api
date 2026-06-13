<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Suspend a teacher.
 */
class SuspendTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for suspending.', 'example' => 'Tạm nghỉ cá nhân'],
        ];
    }
}
