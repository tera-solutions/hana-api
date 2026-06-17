<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockLessonRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'reason.required' => 'Lý do mở khóa là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for unlocking.', 'example' => 'Cần sửa điểm danh'],
        ];
    }
}
