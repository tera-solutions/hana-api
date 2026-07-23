<?php

namespace App\Modules\CRM\Lead\Http\Requests;

use App\Enums\Shared\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Convert a lead into a student. Every field is an override on top of the
 * lead's own data — only send what's missing or needs correcting.
 */
class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'branch_id' => ['nullable', 'integer', 'exists:sys_branches,id'],
            'level_id' => ['nullable', 'integer', 'exists:edu_levels,id'],
            'enrollment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'dob' => ['description' => 'Date of birth override (required if missing on the lead).', 'example' => '2016-05-12'],
            'gender' => ['description' => 'male|female|other override (required if missing on the lead).', 'example' => 'male'],
            'branch_id' => ['description' => 'Branch override (required if missing on the lead).', 'example' => 1],
            'level_id' => ['description' => 'Initial proficiency level id.', 'example' => 1],
            'enrollment_date' => ['description' => 'Defaults to today.', 'example' => '2026-07-23'],
            'note' => ['description' => 'Optional note recorded in the lead history.', 'example' => 'Chuyển đổi sau buổi học thử'],
        ];
    }
}
