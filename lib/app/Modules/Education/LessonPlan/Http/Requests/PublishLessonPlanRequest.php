<?php

namespace App\Modules\Education\LessonPlan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_summary' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'change_summary' => ['description' => 'Summary of what changed in this version.', 'example' => 'Cập nhật từ vựng buổi 5'],
        ];
    }
}
