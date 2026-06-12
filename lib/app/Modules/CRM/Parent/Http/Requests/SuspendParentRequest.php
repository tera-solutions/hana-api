<?php

namespace App\Modules\CRM\Parent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'suspend_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'suspend_date' => [
                'description' => 'Suspension date.',
                'example' => '2026-06-12',
            ],
            'reason' => [
                'description' => 'Reason for suspension.',
                'example' => 'Ngừng liên hệ',
            ],
            'note' => [
                'description' => 'Additional note.',
            ],
        ];
    }
}
