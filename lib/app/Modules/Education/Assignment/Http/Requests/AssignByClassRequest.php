<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignByClassRequest extends FormRequest
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
            'class_room_id.exists' => 'Lớp học không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_room_id' => ['description' => 'Assign to every active student of this class.', 'example' => 1],
        ];
    }
}
