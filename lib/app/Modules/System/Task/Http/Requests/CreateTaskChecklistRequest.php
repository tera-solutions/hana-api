<?php

namespace App\Modules\System\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_completed' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Nội dung checklist là bắt buộc.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Nội dung mục checklist.', 'example' => 'Chuẩn bị giáo án'],
            'is_completed' => ['description' => 'Đã hoàn thành chưa (mặc định false).', 'example' => false],
        ];
    }
}
