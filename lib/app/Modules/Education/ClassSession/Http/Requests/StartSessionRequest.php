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
            // Which of the class's linked plans this session follows — optional,
            // since a session may have none (e.g. an exam day). Only used when
            // the session doesn't already have a Lesson.
            'lesson_plan_id' => ['nullable', 'integer', 'exists:edu_lesson_plans,id'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'note' => ['description' => 'Ghi chú khi bắt đầu buổi học (tùy chọn).', 'example' => 'Bắt đầu đúng giờ.'],
            'lesson_plan_id' => [
                'description' => 'Giáo án buổi học này sẽ theo (tùy chọn, phải thuộc danh sách giáo án của lớp).',
                'example' => 1,
            ],
        ];
    }
}
