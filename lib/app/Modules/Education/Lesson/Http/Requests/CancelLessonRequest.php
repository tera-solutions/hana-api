<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelLessonRequest extends FormRequest
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
            'reason.required' => 'Lý do hủy là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Reason for cancelling.', 'example' => 'Giáo viên nghỉ ốm'],
        ];
    }
}
