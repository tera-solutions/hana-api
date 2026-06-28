<?php

namespace App\Modules\Education\Assignment\Http\Requests;

use App\Modules\Education\Assignment\Enums\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create input per spec §VI. assignment_code is auto-generated; course/level/lesson/class,
 * description and submission policy flags are set later via update.
 */
class CreateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_name' => ['required', 'string', 'max:255'],
            'assignment_type' => ['required', Rule::in(AssignmentType::values())],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'instruction' => ['required', 'string', 'max:10000'],
            'max_score' => ['required', 'numeric', 'gt:0'],          // BR003
            'due_date' => ['required', 'date', 'after:now'],         // BR002
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_name.required' => 'Tên bài tập là bắt buộc.',       // BR001
            'assignment_type.required' => 'Loại bài tập là bắt buộc.',
            'assignment_type.in' => 'Loại bài tập không hợp lệ.',
            'instruction.required' => 'Hướng dẫn là bắt buộc.',
            'max_score.gt' => 'Điểm tối đa phải lớn hơn 0.',
            'due_date.after' => 'Hạn nộp phải sau thời điểm hiện tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'assignment_name' => ['description' => 'Assignment name.', 'example' => 'Unit 1 Homework'],
            'assignment_type' => ['description' => 'Type: homework, worksheet, quiz, writing, speaking, listening, reading, project, exam_practice.', 'example' => 'homework'],
            'avatar' => [
                'description' => 'Avatar URL.',
                'example' => 'https://cdn.hana.edu.vn/a.png',
            ],
            'instruction' => ['description' => 'Instruction for students.', 'example' => 'Hoàn thành bài tập trang 10.'],
            'max_score' => ['description' => 'Maximum score (> 0).', 'example' => 10],
            'due_date' => ['description' => 'Due date (Y-m-d H:i:s), must be in the future.', 'example' => '2026-07-10 23:59:00'],
        ];
    }
}
