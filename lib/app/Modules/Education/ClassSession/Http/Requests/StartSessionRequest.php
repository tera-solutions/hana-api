<?php

namespace App\Modules\Education\ClassSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'note' => ['description' => 'Ghi chú khi bắt đầu buổi học (tùy chọn).', 'example' => 'Bắt đầu đúng giờ.'],
        ];
    }
}
