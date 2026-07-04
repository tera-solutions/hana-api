<?php

namespace App\Modules\System\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Nội dung mục checklist.', 'example' => 'In tài liệu'],
            'is_completed' => ['description' => 'Đánh dấu hoàn thành.', 'example' => true],
        ];
    }
}
