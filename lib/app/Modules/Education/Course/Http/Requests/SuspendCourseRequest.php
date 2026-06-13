<?php

namespace App\Modules\Education\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendCourseRequest extends FormRequest
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
            'reason' => [
                'description' => 'Reason for suspending the course.',
                'example' => 'Tạm dừng tuyển sinh',
            ],
        ];
    }
}
