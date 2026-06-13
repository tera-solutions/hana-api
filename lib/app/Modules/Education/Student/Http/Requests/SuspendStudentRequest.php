<?php

namespace App\Modules\Education\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stop_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'stop_date' => [
                'description' => 'Suspension date.',
                'example' => '2026-06-12',
            ],
            'reason' => [
                'description' => 'Reason for suspension.',
                'example' => 'Nghỉ dài hạn',
            ],
            'note' => [
                'description' => 'Additional note.',
            ],
        ];
    }
}
