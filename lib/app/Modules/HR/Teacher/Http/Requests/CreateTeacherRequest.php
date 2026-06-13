<?php

namespace App\Modules\HR\Teacher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:hr_teachers,code'],
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'business_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'salary_per_hour' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'code' => [
                'description' => 'Unique teacher code.',
                'example' => 'T0001',
            ],
            'name' => [
                'description' => 'Teacher full name.',
                'example' => 'Jane Doe',
            ],
            'user_id' => [
                'description' => 'Linked user id.',
                'example' => 1,
            ],
            'business_id' => [
                'description' => 'Owning business id.',
                'example' => 1,
            ],
            'type' => [
                'description' => 'Teacher type.',
                'example' => 'teacher',
            ],
            'status' => [
                'description' => 'active|inactive.',
                'example' => 'active',
            ],
            'salary_per_hour' => [
                'description' => 'Hourly salary.',
                'example' => 150000,
            ],
        ];
    }
}
