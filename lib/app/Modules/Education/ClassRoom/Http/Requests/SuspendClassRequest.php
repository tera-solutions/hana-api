<?php

namespace App\Modules\Education\ClassRoom\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendClassRequest extends FormRequest
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
            'reason.required' => 'Vui lòng nhập lý do tạm ngừng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Lý do tạm ngừng lớp học.', 'example' => 'Giáo viên nghỉ bệnh dài hạn.'],
        ];
    }
}
