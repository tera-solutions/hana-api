<?php

namespace App\Modules\Education\StudentLevel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'target_level_id' => ['description' => 'Explicit target level; defaults to the next level in the path.', 'example' => 2],
            'reason' => ['description' => 'Promotion note.', 'example' => 'Đạt yêu cầu lên cấp.'],
        ];
    }
}
