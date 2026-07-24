<?php

namespace App\Modules\Education\Level\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct', 'exists:edu_levels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required' => 'Danh sách thứ tự là bắt buộc.',
            'order.*.exists' => 'Cấp độ không tồn tại.',
            'order.*.distinct' => 'Danh sách thứ tự không được trùng lặp.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'order' => ['description' => 'Danh sách id cấp độ theo thứ tự mới (cùng khóa học).', 'example' => [2, 1, 3]],
        ];
    }
}
