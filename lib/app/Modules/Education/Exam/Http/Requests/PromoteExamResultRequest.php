<?php

namespace App\Modules\Education\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Promotion input (spec §XIII). The target level is optional — when omitted the student
 * advances to the next level in the course path.
 */
class PromoteExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'target_level_id' => ['description' => 'Explicit target level (optional; defaults to the next level).', 'example' => 2],
            'reason' => ['description' => 'Reason for the promotion.', 'example' => 'Đạt điểm xét lên cấp.'],
        ];
    }
}
