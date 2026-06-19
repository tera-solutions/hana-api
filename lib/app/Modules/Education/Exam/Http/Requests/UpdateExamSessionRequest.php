<?php

namespace App\Modules\Education\Exam\Http\Requests;

use App\Modules\Education\Exam\Enums\ExamSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'exam_date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'status' => ['sometimes', Rule::in(ExamSessionStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_room_id' => ['description' => 'Class to seat (optional).', 'example' => 1],
            'room_id' => ['description' => 'Physical room (optional).', 'example' => 1],
            'teacher_id' => ['description' => 'Invigilator (optional).', 'example' => 1],
            'exam_date' => ['description' => 'Exam date (Y-m-d).', 'example' => '2026-07-15'],
            'start_time' => ['description' => 'Start time (H:i).', 'example' => '09:00'],
            'end_time' => ['description' => 'End time (H:i), after start_time.', 'example' => '10:30'],
            'status' => ['description' => 'Status: scheduled, in_progress, closed.', 'example' => 'closed'],
        ];
    }
}
