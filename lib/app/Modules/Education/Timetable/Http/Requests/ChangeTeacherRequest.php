<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:hr_teachers,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Vui lòng chọn giáo viên dạy thay.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teacher_id' => ['description' => 'Giáo viên mới phụ trách buổi học.', 'example' => 2],
            'reason' => ['description' => 'Lý do đổi giáo viên.', 'example' => 'Giáo viên A nghỉ đột xuất.'],
        ];
    }
}
