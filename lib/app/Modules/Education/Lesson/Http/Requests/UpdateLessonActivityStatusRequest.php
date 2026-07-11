<?php

namespace App\Modules\Education\Lesson\Http\Requests;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonActivityStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(LessonActivityStatus::cases(), 'value'))],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'Lesson activity status.',
                'example' => 'in_progress',
            ],
        ];
    }
}
