<?php

namespace App\Modules\Education\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterByClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_room_id' => ['required', 'integer', 'exists:edu_classes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_room_id.required' => 'Lớp học là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_room_id' => ['description' => 'Class whose active students are auto-registered.', 'example' => 1],
        ];
    }
}
