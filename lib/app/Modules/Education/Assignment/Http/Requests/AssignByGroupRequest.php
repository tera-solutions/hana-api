<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignByGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:edu_levels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_id.required' => 'Nhóm trình độ là bắt buộc.',
            'level_id.exists' => 'Trình độ không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'level_id' => ['description' => 'Assign to every active student at this level (group).', 'example' => 1],
        ];
    }
}
