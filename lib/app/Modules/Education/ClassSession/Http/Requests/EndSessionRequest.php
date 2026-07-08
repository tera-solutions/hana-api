<?php

namespace App\Modules\Education\ClassSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndSessionRequest extends FormRequest
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
            'note' => ['description' => 'Ghi chú khi kết thúc sớm (tùy chọn).', 'example' => 'Học viên hoàn thành bài sớm.'],
        ];
    }
}
