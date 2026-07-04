<?php

namespace App\Modules\System\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Nội dung bình luận là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'comment' => ['description' => 'Nội dung bình luận.', 'example' => 'Đã liên hệ phụ huynh, hẹn đóng vào thứ 6.'],
        ];
    }
}
